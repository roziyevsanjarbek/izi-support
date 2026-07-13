<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Images\Attachment;
use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskRead;
use App\Services\Images\AttachmentService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $tasks = $this->buildIndexQuery($request)
            ->with([
                'creator',
                'completedBy',
                'rejectedBy',
                'reads.user',
                'conversation.permissions',
            ])
            ->withCount('attachments')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $tasks->getCollection()->transform(function ($task) use ($user) {
            $permission = $task->conversation?->permissions?->firstWhere('user_id', $user->id);

            $task->unread_count = (int) ($permission?->unread_count ?? 0);

            return $this->decorateTaskActions($task, $user);
        });

        return view('pages.tasks.index', [
            'operation' => false,
            'tasks' => $tasks,
            'stats' => $this->taskStats(),
            'mode' => 'all',
        ]);
    }


    private function buildIndexQuery(Request $request)
    {
        $query = Task::query()->where('type', 'default');

        $filter = (array) $request->input('filter', []);

        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (!empty($filter['name'])) {
            $query->where('name', 'like', '%' . $filter['name'] . '%');
        }

        if (!empty($filter['description'])) {
            $query->where('description', 'like', '%' . $filter['description'] . '%');
        }

        return $query;
    }

    public function store(Request $request, AttachmentService $attachmentService)
    {
        $validated = $this->validateTaskPayload($request, true);

        $task = DB::transaction(function () use ($validated, $attachmentService) {
            $conversation = Conversation::create([
                'type' => 'task',
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $task = Task::create([
                'created_by' => auth()->id(),
                'conversation_id' => $conversation->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
                'type' => 'default',
            ]);

            ConversationPermission::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => auth()->id(),
                ],
                [
                    'role' => 'leader',
                    'notifications' => true,
                    'can_add_user' => true,
                    'can_remove_user' => true,
                    'can_delete_message' => true,
                    'can_change_name' => true,
                    'can_pin_message' => true,
                    'can_send_messages' => true,
                ]
            );

            $this->storeTaskAttachments(
                task: $task,
                files: $validated['attachments'] ?? [],
                attachmentService: $attachmentService
            );

            return $task;
        });

        $task->load([
            'creator',
            'completedBy',
            'rejectedBy',
            'reads.user',
            'conversation',
            'attachments',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $this->decorateTaskForResponse($task),
        ], 201);
    }


    public function update(Request $request, Task $task)
    {
        abort_unless(auth()->user()?->canUpdateTaskStatus($task), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Task status updated successfully',
            'data' => $this->decorateTaskForResponse(
                $task->fresh(['creator', 'completedBy', 'rejectedBy', 'reads.user', 'attachments'])
            ),
        ]);
    }

    public function complete(Request $request, Task $task)
    {
        $user = auth()->user();

        if ($task->status === 'completed') {
            return response()->json([
                'message' => 'Task is already completed',
            ], 422);
        }

        $task->update([
            'status' => 'completed',
            'completed_by' => $user->id,
            'end_date' => now(),
        ]);

        app(TelegramNotificationService::class)
            ->scheduleTaskCompletedNotification($task->fresh(['creator', 'completedBy']));

        return response()->json([
            'message' => 'Task completed successfully',
            'data' => $this->decorateTaskForResponse(
                $task->fresh(['creator', 'completedBy', 'rejectedBy', 'reads.user', 'attachments'])
            ),
        ]);
    }

    public function reject(Request $request, Task $task)
    {
        $user = auth()->user();

        if ($task->status === 'rejected') {
            return response()->json([
                'message' => 'Task is already rejected',
            ], 422);
        }

        $task->update([
            'status' => 'rejected',
            'rejected_by' => $user->id,
            'end_date' => now(),
        ]);

        return response()->json([
            'message' => 'Task rejected successfully',
            'data' => $this->decorateTaskForResponse(
                $task->fresh(['creator', 'completedBy', 'rejectedBy', 'reads.user', 'attachments'])
            ),
        ]);
    }

    public function show(Task $task, TelegramNotificationService $telegramNotificationService): View
    {
        $user = auth()->user();
        abort_unless($user, 401);

        $this->markAsReadForShow($task, $user, $telegramNotificationService);

        $task->load([
            'creator',
            'completedBy',
            'rejectedBy',
            'reads.user',
            'conversation.permissions.user',
            'conversation.messages.sender',
            'attachments',
        ]);

        $conversation = $task->conversation;

        if ($conversation) {
            $permission = $conversation->permissions()
                ->where('user_id', $user->id)
                ->first();

            if (!$permission) {
                $conversation->permissions()->create([
                    'user_id' => $user->id,
                    'role' => 'member',
                    'notifications' => true,
                ]);
            }

            $messages = $conversation->messages()
                ->with('sender')
                ->latest('id')
                ->take(50)
                ->get()
                ->sortBy('id')
                ->values();

            $users = $conversation->permissions()->with('user')->get();
        } else {
            $messages = collect();
            $users = collect();
        }

        $canReject = in_array($task->status, ['pending', 'in_progress'])
            && $task->created_by === $user->id
            && ($user->hasPermission('reject_tasks') || $user->isSuperAdmin());

        $canEdit = in_array($task->status, ['pending']) && $task->created_by === $user->id;

        return view('pages.tasks.show', [
            'task' => $this->decorateTaskForResponse($task->fresh([
                'creator',
                'completedBy',
                'rejectedBy',
                'reads.user',
                'conversation.permissions.user',
                'attachments',
            ])),
            'conversation' => $conversation,
            'messages' => $messages,
            'users' => $users,
            'canReject' => $canReject,
            'canEdit' => $canEdit,
        ]);
    }

    public function updateDetails(Request $request, Task $task, AttachmentService $attachmentService)
    {
        abort_unless(auth()->user()?->canUpdateTaskStatus($task) || $task->created_by === auth()->id(), 403);

        $validated = $this->validateTaskPayload($request, false);

        $deletedIds = collect($validated['deleted_attachment_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $newFiles = $validated['attachments'] ?? [];

        DB::transaction(function () use ($task, $validated, $deletedIds, $newFiles, $attachmentService) {
            $task->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (!empty($deletedIds)) {
                $attachmentsToDelete = $task->attachments()
                    ->whereIn('id', $deletedIds)
                    ->get();

                foreach ($attachmentsToDelete as $attachment) {
                    $attachmentService->delete($attachment);
                }
            }

            $currentCountAfterDelete = $task->attachments()
                ->whereNotIn('id', $deletedIds)
                ->count();

            $incomingCount = count($newFiles);

            if (($currentCountAfterDelete + $incomingCount) > 5) {
                throw ValidationException::withMessages([
                    'attachments' => 'Task uchun maksimal 5 ta attachment mumkin.',
                ]);
            }

            if (!empty($newFiles)) {
                $attachmentService->storeMany(
                    files: $newFiles,
                    attachable: $task,
                    collection: 'gallery',
                    disk: 'public',
                    directory: 'tasks'
                );
            }
        });

        $task->load([
            'creator',
            'completedBy',
            'rejectedBy',
            'reads.user',
            'conversation.permissions.user',
            'attachments',
        ]);

        return response()->json([
            'message' => 'Task updated',
            'data' => $this->decorateTaskForResponse($task),
        ]);
    }


    public function destroyAttachment(Task $task, Attachment $attachment, AttachmentService $attachmentService)
    {
        abort_unless((int) $attachment->attachable_id === (int) $task->id, 404);
        abort_unless((string) $attachment->attachable_type === Task::class, 404);
        abort_unless($task->created_by === auth()->id() || auth()->user()?->isSuperAdmin(), 403);

        $attachmentService->delete($attachment);

        return response()->json([
            'message' => 'Attachment deleted',
        ]);
    }
    private function validateTaskPayload(Request $request, bool $requireDescription = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => [$requireDescription ? 'required' : 'nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:102400'],
            'deleted_attachment_ids' => ['nullable', 'array'],
            'deleted_attachment_ids.*' => ['integer', 'exists:attachments,id'],
        ];

        return $request->validate($rules);
    }

    private function markAsReadForShow(
        Task $task,
        $user,
        TelegramNotificationService $telegramNotificationService
    ): void {
        $isCreator = (int) $task->created_by === (int) $user->id;

        if ($isCreator) {
            return;
        }

        $wasAlreadyRead = TaskRead::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->exists();

        $read = TaskRead::firstOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $user->id,
            ],
            [
                'view_count' => 1,
                'first_viewed_at' => now(),
                'last_viewed_at' => now(),
            ]
        );

        if ($wasAlreadyRead) {
            $read->increment('view_count');

            $read->update([
                'last_viewed_at' => now(),
            ]);
        }

        if (!$wasAlreadyRead && $task->status === 'pending') {
            $task->update([
                'status' => 'in_progress',
                'start_date' => now(),
            ]);

            $telegramNotificationService->scheduleTaskStartedNotification(
                $task->fresh(['creator']),
                $user
            );
        }
    }



    private function storeTaskAttachments(Task $task, array $files, AttachmentService $attachmentService): void
    {
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return;
        }

        $currentCount = $task->attachments()->count();
        $incomingCount = count($files);

        if (($currentCount + $incomingCount) > 5) {
            throw ValidationException::withMessages([
                'attachments' => 'Task uchun maksimal 5 ta attachment mumkin.',
            ]);
        }

        $attachmentService->storeMany(
            files: $files,
            attachable: $task,
            collection: 'gallery',
            disk: 'public',
            directory: 'tasks'
        );
    }

    private function decorateTaskForResponse(Task $task): Task
    {
        $task->setAttribute('update_details_url', route('tasks.update-details', $task));
        $task->setAttribute('complete_url', route('tasks.complete', $task));
        $task->setAttribute('reject_url', route('tasks.reject', $task));
        $task->setAttribute('view_url', route('tasks.show', $task));
        $task->setAttribute('attachments_count', $task->attachments?->count() ?? 0);

        if ($task->relationLoaded('attachments')) {
            $task->setAttribute('attachments', $task->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'collection' => $attachment->collection,
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                    'original_name' => $attachment->original_name,
                    'file_name' => $attachment->file_name,
                    'extension' => $attachment->extension,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'width' => $attachment->width,
                    'height' => $attachment->height,
                    'order' => $attachment->order,
                    'meta' => $attachment->meta,
                    'url' => $attachment->url,
                ];
            })->values());
        }

        return $task;
    }


    private function taskStats(): array
    {
        return [
            'total' => Task::where('type', 'default')->count(),
            'pending' => Task::where('type', 'default')->where('status', 'pending')->count(),
            'completed' => Task::where('type', 'default')->where('status', 'completed')->count(),
        ];
    }

    private function decorateTaskActions(Task $task, $user): Task
    {
        $task->setAttribute('can_edit', $user->can('update', $task));

        return $task;
    }
}
