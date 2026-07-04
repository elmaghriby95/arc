<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $documentsQuery = Document::query();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $documentsQuery->whereIn('department_id', $ids);
        }

        $departmentsQuery = Department::where('is_active', true);
        if ($ids = $user->orgScopeDepartmentIds()) {
            $departmentsQuery->whereIn('id', $ids);
        }

        return view('dashboard', [
            'stats' => [
                'documents' => (clone $documentsQuery)->count(),
                'departments' => (clone $departmentsQuery)->count(),
                'categories' => Category::where('is_active', true)->count(),
                'users' => $user->isAdmin() ? User::count() : null,
            ],
            'recentDocuments' => (clone $documentsQuery)
                ->with(['department', 'category', 'uploader'])
                ->latest()
                ->limit(5)
                ->get(),
            'orgBreadcrumb' => $user->orgBreadcrumb(),
        ]);
    }
}
