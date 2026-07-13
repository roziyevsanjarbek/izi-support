<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\Tasks\Task;
use App\Models\User;
use Carbon\CarbonPeriod;

class PageController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'tasks_total' => Task::count(),
            'users_total' => User::count(),
            'pending_total' => Task::where('status', 'pending')->count(),
            'in_progress_total' => Task::where('status', 'in_progress')->count(),
            'completed_total' => Task::where('status', 'completed')->count(),
        ];

        $statusRows = Task::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $topCreators = Task::query()
            ->whereNotNull('created_by')
            ->selectRaw('created_by, COUNT(*) as tasks_count')
            ->groupBy('created_by')
            ->orderByDesc('tasks_count')
            ->limit(10)
            ->with('creator:id,name')
            ->get()
            ->map(function (Task $task) {
                return [
                    'name' => $task->creator?->name ?? 'Unknown',
                    'total' => (int) $task->tasks_count,
                ];
            })
            ->values();

        $topCompleters = Task::query()
            ->whereNotNull('completed_by')
            ->selectRaw('completed_by, COUNT(*) as tasks_count')
            ->groupBy('completed_by')
            ->orderByDesc('tasks_count')
            ->limit(10)
            ->with('completedBy:id,name')
            ->get()
            ->map(function (Task $task) {
                return [
                    'name' => $task->completedBy?->name ?? 'Unknown',
                    'total' => (int) $task->tasks_count,
                ];
            })
            ->values();

        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $dailyMap = Task::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at'])
            ->groupBy(function ($task) {
                return $task->created_at->format('Y-m-d');
            })
            ->map
            ->count();

        $period = CarbonPeriod::create($start, '1 day', $end);

        $dailyLabels = [];
        $dailyData = [];

        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $dailyLabels[] = $date->format('d M');
            $dailyData[] = (int) ($dailyMap[$key] ?? 0);
        }

        $charts = [
            'status' => [
                'labels' => $statusRows->map(function ($row) {
                    return ucfirst(str_replace('_', ' ', $row->status));
                })->all(),
                'data' => $statusRows->map(function ($row) {
                    return (int) $row->total;
                })->all(),
            ],
            'creators' => [
                'labels' => $topCreators->pluck('name')->all(),
                'data' => $topCreators->pluck('total')->all(),
            ],
            'completers' => [
                'labels' => $topCompleters->pluck('name')->all(),
                'data' => $topCompleters->pluck('total')->all(),
            ],
            'daily' => [
                'labels' => $dailyLabels,
                'data' => $dailyData,
            ],
        ];

        return view('pages.dashboard', compact('stats', 'charts', 'topCreators', 'topCompleters'));
    }
}