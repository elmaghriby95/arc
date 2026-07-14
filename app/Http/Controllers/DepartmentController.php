<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('departments.index', [
            'departments' => Department::withCount('documents')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('departments.create', [
            'parents' => Department::whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department = Department::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $logger->logDepartmentCreated($request->user(), $department, $request);

        return redirect()
            ->route('departments.index')
            ->with('success', __('messages.department.created'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department,
            'parents' => Department::whereNull('parent_id')->where('id', '!=', $department->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Department $department, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,'.$department->id],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldValues = $department->only(['name', 'code', 'unit_label', 'parent_id', 'is_active']);

        $department->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $logger->logDepartmentUpdated(
            $request->user(),
            $department,
            $oldValues,
            $department->only(['name', 'code', 'unit_label', 'parent_id', 'is_active']),
            $request,
        );

        return redirect()
            ->route('departments.index')
            ->with('success', __('messages.department.updated'));
    }

    public function destroy(Request $request, Department $department, UserActivityLogger $logger): RedirectResponse
    {
        $logger->logDepartmentDeleted($request->user(), $department, $request);
        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', __('messages.department.deleted'));
    }
}
