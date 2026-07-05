<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\TransactionAttachment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $attachmentsQuery = TransactionAttachment::query()->whereHas('transaction', function ($query) use ($user) {
            if ($ids = $user->orgScopeDepartmentIds()) {
                $query->whereIn('department_id', $ids);
            }
        });

        $departmentsQuery = Department::where('is_active', true);
        if ($ids = $user->orgScopeDepartmentIds()) {
            $departmentsQuery->whereIn('id', $ids);
        }

        return view('dashboard', [
            'stats' => [
                'documents' => (clone $attachmentsQuery)->count(),
                'departments' => (clone $departmentsQuery)->count(),
                'categories' => Category::where('is_active', true)->count(),
                'users' => $user->isAdmin() ? User::count() : null,
            ],
            'recentAttachments' => (clone $attachmentsQuery)
                ->with(['transaction.department', 'transaction.status', 'uploader'])
                ->latest()
                ->limit(5)
                ->get(),
            'orgBreadcrumb' => $user->orgBreadcrumb(),
        ]);
    }
}
