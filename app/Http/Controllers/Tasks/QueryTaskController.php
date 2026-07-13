<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreQueryTaskRequest;
use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\Images\AttachmentService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueryTaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $filter = (array) $request->input('filter', []);

        $baseQuery = Task::query()
            ->where('type', 'operation');

        if (! $user->hasRole('superadmin')) {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        if (! empty($filter['status'])) {
            $baseQuery->where('status', $filter['status']);
        }

        if (! empty($filter['name'])) {
            $baseQuery->where('name', 'like', '%' . $filter['name'] . '%');
        }

        if (! empty($filter['description'])) {
            $baseQuery->where('description', 'like', '%' . $filter['description'] . '%');
        }

        $tasks = (clone $baseQuery)
            ->with([
                'creator',
                'completedBy',
                'reads.user',
                'conversation.permissions',
            ])
            ->withCount('attachments')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $tasks->getCollection()->transform(function ($task) use ($user) {
            $permission = $task->conversation?->permissions?->firstWhere('user_id', $user->id);

            $task->unread_count = (int) ($permission?->unread_count ?? 0);

            return $task;
        });

        return view('pages.tasks.index', [
            'operation' => true,
            'tasks' => $tasks,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function store(
        StoreQueryTaskRequest $request,
        TelegramNotificationService $telegramService,
        AttachmentService $attachmentService
    ): JsonResponse {
        $data = $request->validated();

        $assignedUser = User::find($data['operation_id'] ?? null);

        if (! $assignedUser) {
            return response()->json([
                'message' => 'Operation user not found',
            ], 404);
        }

        $task = DB::transaction(function () use ($data, $assignedUser, $attachmentService) {
            $conversation = Conversation::create([
                'type' => 'task',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $task = Task::create([
                'created_by' => auth()->id(),
                'conversation_id' => $conversation->id,
                'query_id' => $data['query_id'] ?? null,
                'assigned_to' => $assignedUser->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => 'operation',
                'status' => 'pending',
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

            ConversationPermission::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $assignedUser->id,
                ],
                [
                    'role' => 'member',
                    'notifications' => true,
                    'can_add_user' => false,
                    'can_remove_user' => false,
                    'can_delete_message' => false,
                    'can_change_name' => false,
                    'can_pin_message' => false,
                    'can_send_messages' => true,
                ]
            );

            $this->storeTaskAttachments(
                task: $task,
                files: $data['attachments'] ?? [],
                attachmentService: $attachmentService
            );

            return $task;
        });

        $task->load([
            'creator',
            'completedBy',
            'reads.user',
            'conversation',
            'attachments',
        ]);

        $telegramService->scheduleTaskCreatedNotification($assignedUser, $task);

        return response()->json([
            'message' => 'Task successfully created',
            'data' => $this->decorateTaskForResponse($task),
        ], 201);
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
}