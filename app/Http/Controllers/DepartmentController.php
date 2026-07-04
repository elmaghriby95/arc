<?php

namespace App\Http\Controllers;

use App\Models\Department;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'تم إضافة القسم بنجاح.');
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department,
            'parents' => Department::whereNull('parent_id')->where('id', '!=', $department->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,'.$department->id],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'تم تحديث القسم بنجاح.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'تم حذف القسم بنجاح.');
    }
}
