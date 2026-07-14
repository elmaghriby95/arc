<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentCodeService;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        return view('settings.organization.index', [
            'departments' => Department::tree(),
            'totalDepartments' => Department::count(),
            'totalUsers' => User::count(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'unitLabelSuggestions' => [
                __('organization.unit_label.sector'),
                __('organization.unit_label.administration'),
                __('organization.unit_label.department'),
                __('organization.unit_label.unit'),
                __('organization.unit_label.office'),
                __('organization.unit_label.branch'),
                __('organization.unit_label.directorate'),
            ],
        ]);
    }

    public function store(Request $request, DepartmentCodeService $departmentCodes, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'head_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department = Department::create([
            ...$validated,
            'code' => $departmentCodes->generateForParent($validated['parent_id'] ?? null),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $logger->logDepartmentCreated($request->user(), $department, $request);

        return redirect()
            ->route('settings.organization.index')
            ->with('success', __('messages.organization.created'));
    }

    public function update(Request $request, Department $department, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'head_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldValues = $department->only(['name', 'code', 'unit_label', 'parent_id', 'head_id', 'is_active']);

        $department->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $logger->logDepartmentUpdated(
            $request->user(),
            $department,
            $oldValues,
            $department->only(['name', 'code', 'unit_label', 'parent_id', 'head_id', 'is_active']),
            $request,
        );

        return redirect()
            ->route('settings.organization.index')
            ->with('success', __('messages.organization.updated'));
    }

    public function destroy(Request $request, Department $department, UserActivityLogger $logger): RedirectResponse
    {
        $logger->logDepartmentDeleted($request->user(), $department, $request);
        $department->delete();

        return redirect()
            ->route('settings.organization.index')
            ->with('success', __('messages.organization.deleted'));
    }
}
