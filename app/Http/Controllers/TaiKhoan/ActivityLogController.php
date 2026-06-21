<?php

namespace App\Http\Controllers\TaiKhoan;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
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

        $activityLogs = ActivityLog::query()
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
            ->latest('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('content.tai-khoan.activity-logs.index', [
            'activityLogs' => $activityLogs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'username']),
            'modules' => ActivityLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('content.tai-khoan.activity-logs.show', compact('activityLog'));
    }
}
