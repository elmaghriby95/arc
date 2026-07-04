<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            'unitLabelSuggestions' => ['قطاع', 'إدارة', 'قسم', 'وحدة', 'مكتب', 'فرع', 'مديرية'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'head_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            ...$validated,
            'code' => $this->generateUniqueCode($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('settings.organization.index')
            ->with('success', 'تم إضافة الوحدة التنظيمية بنجاح.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'head_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.organization.index')
            ->with('success', 'تم تحديث الوحدة التنظيمية بنجاح.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()
            ->route('settings.organization.index')
            ->with('success', 'تم حذف الوحدة التنظيمية بنجاح.');
    }

    private function generateUniqueCode(string $name): string
    {
        $base = Str::upper(Str::substr(Str::slug($name, ''), 0, 8));

        if ($base === '') {
            $base = 'UNIT';
        }

        $code = $base;
        $counter = 1;

        while (Department::where('code', $code)->exists()) {
            $code = $base.'-'.$counter;
            $counter++;
        }

        return $code;
    }
}
