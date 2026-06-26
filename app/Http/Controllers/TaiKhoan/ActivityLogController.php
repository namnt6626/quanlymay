<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogFileStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'date_from' => trim((string) $request->input('date_from')),
            'date_to' => trim((string) $request->input('date_to')),
            'user_id' => $request->input('user_id'),
            'module' => trim((string) $request->input('module')),
            'action' => trim((string) $request->input('action')),
            'q' => trim((string) $request->input('q')),
            'per_page' => paginationPerPage(),
        ];

        $dbLogs = ActivityLog::query()
            ->with('user')
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['user_id'], fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when($filters['module'] !== '', fn ($query) => $query->where('module', $filters['module']))
            ->when($filters['action'] !== '', fn ($query) => $query->where('action', $filters['action']))
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $keyword = $filters['q'];
                $query->where(function ($query) use ($keyword): void {
                    $query->where('description', 'like', "%{$keyword}%")
                        ->orWhere('user_name', 'like', "%{$keyword}%")
                        ->orWhere('ip_address', 'like', "%{$keyword}%")
                        ->orWhere('route_name', 'like', "%{$keyword}%")
                        ->orWhere('url', 'like', "%{$keyword}%");
                });
            })
            ->get()
            ->each(function (ActivityLog $log): void {
                $log->setAttribute('source_key', (string) $log->getKey());
                $log->setAttribute('source_type', 'database');
            });

        $activityLogs = $this->paginateLogs(
            $dbLogs
                ->concat(ActivityLogFileStore::all($filters))
                ->sortByDesc(fn ($log): string => $log->created_at?->format('Y-m-d H:i:s.u') ?? '')
                ->values(),
            $filters['per_page'],
            $request
        );

        $modules = ActivityLog::query()->select('module')->distinct()->pluck('module')
            ->concat(ActivityLogFileStore::modules())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $actions = ActivityLog::query()->select('action')->distinct()->pluck('action')
            ->concat(ActivityLogFileStore::actions())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('content.tai-khoan.activity-logs.index', [
            'activityLogs' => $activityLogs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'username']),
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }

    public function show(string $activityLog): View
    {
        $activityLog = str_starts_with($activityLog, 'file_')
            ? ActivityLogFileStore::find($activityLog)
            : ActivityLog::query()->findOrFail($activityLog);

        abort_if(! $activityLog, 404);

        return view('content.tai-khoan.activity-logs.show', compact('activityLog'));
    }

    private function paginateLogs(Collection $logs, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator(
            $logs->forPage($page, $perPage)->values(),
            $logs->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ));
    }
}
