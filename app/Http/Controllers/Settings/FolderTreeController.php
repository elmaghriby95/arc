<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FolderTreeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $user->orgScopeDepartmentIds();

        return view('settings.folders.index', [
            'folders' => Folder::scopedTree($scope),
            'totalFolders' => Folder::scopedQuery($scope)->count(),
            'parents' => Folder::scopedQuery($scope)->orderBy('name')->get(),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'breadcrumbs' => Department::breadcrumbMap(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id'],
            'department_id' => ['required', 'exists:departments,id'],
            ...$this->locationValidationRules($user),
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['parent_id'] ?? null) {
            $parent = Folder::findOrFail($validated['parent_id']);
            $this->authorizeFolderAccess($user, $parent);
        }

        $departmentId = $validated['department_id'];

        if (! $user->canAccessDepartment($departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => __('messages.folder.department_denied')]);
        }

        $this->stripLocationFieldsUnlessAllowed($validated, $user);

        Folder::create([
            ...$validated,
            'department_id' => $departmentId,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('settings.folders.index')
            ->with('success', __('messages.folder.created'));
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeFolderAccess($user, $folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:folders,id', 'not_in:'.$folder->id],
            'department_id' => ['required', 'exists:departments,id'],
            ...$this->locationValidationRules($user),
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['parent_id'] ?? null) {
            $this->authorizeFolderAccess($user, Folder::findOrFail($validated['parent_id']));
        }

        $departmentId = $validated['department_id'];

        if (! $user->canAccessDepartment($departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => __('messages.folder.department_denied')]);
        }

        $this->stripLocationFieldsUnlessAllowed($validated, $user, $folder);

        $folder->update([
            ...$validated,
            'department_id' => $departmentId,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.folders.index')
            ->with('success', __('messages.folder.updated'));
    }

    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorizeFolderAccess($request->user(), $folder);

        $folder->delete();

        return redirect()
            ->route('settings.folders.index')
            ->with('success', __('messages.folder.deleted'));
    }

    private function authorizeFolderAccess(User $user, Folder $folder): void
    {
        if (! $user->canAccessFolder($folder)) {
            abort(403, __('messages.folder.access_denied'));
        }
    }

    /** @return array<string, list<string>> */
    private function locationValidationRules(User $user): array
    {
        $requirement = $user->hasPermission(Permission::SettingsFoldersLocationEdit->value)
            ? 'required'
            : 'nullable';

        return [
            'cabinet_number' => [$requirement, 'string', 'max:50'],
            'row_number' => [$requirement, 'string', 'max:50'],
            'box_number' => [$requirement, 'string', 'max:50'],
        ];
    }

    /** @param  array<string, mixed>  $validated */
    private function stripLocationFieldsUnlessAllowed(array &$validated, User $user, ?Folder $folder = null): void
    {
        if ($user->hasPermission(Permission::SettingsFoldersLocationEdit->value)) {
            return;
        }

        if ($folder) {
            $validated['cabinet_number'] = $folder->cabinet_number;
            $validated['row_number'] = $folder->row_number;
            $validated['box_number'] = $folder->box_number;

            return;
        }

        unset($validated['cabinet_number'], $validated['row_number'], $validated['box_number']);
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user): array
    {
        $options = Department::optionsForSelect();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array($option['id'], $ids, true)
            ));
        }

        return $options;
    }
}
