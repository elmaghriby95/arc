<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Language;
use App\Models\Role;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $folderScope = $user?->orgScopeDepartmentIds();

        $roleCounts = Role::withCount('users')->orderBy('name')->get();

        return view('settings.index', [
            'stats' => [
                'users' => User::count(),
                'departments' => Department::where('is_active', true)->count(),
                'roles' => Role::count(),
                'documentTypes' => DocumentType::where('is_active', true)->count(),
                'transactionTypes' => TransactionType::where('is_active', true)->count(),
                'transactionStatuses' => TransactionStatus::where('is_active', true)->count(),
                'folders' => Folder::scopedQuery($folderScope)->where('is_active', true)->count(),
                'languages' => Language::where('is_active', true)->count(),
            ],
            'roleCounts' => $roleCounts,
            'recentUsers' => User::with(['department', 'role'])->latest()->limit(5)->get(),
        ]);
    }
}
