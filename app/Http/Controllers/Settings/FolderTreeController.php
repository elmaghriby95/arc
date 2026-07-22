<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Folder;
use App\Models\User;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FolderTreeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $scope = $user->folderOrgScopeDepartmentIds();

        return view('settings.folders.index', [
            'folders' => Folder::scopedTree($scope),
            'totalFolders' => Folder::scopedQuery($scope)->count(),
            'parents' => Folder::scopedQuery($scope)->orderBy('name')->get(),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'breadcrumbs' => Department::breadcrumbMap(),
        ]);
    }

    public function store(Request $request, UserActivityLogger $logger): RedirectResponse
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

        if (! $user->canAccessDepartmentInFolderScope($departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => __('messages.folder.department_denied')]);
        }

        $this->stripLocationFieldsUnlessAllowed($validated, $user);

        $folder = Folder::create([
            ...$validated,
            'department_id' => $departmentId,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $logger->logFolderCreated($user, $folder, $request);

        return redirect()
            ->route('settings.folders.index')
            ->with('success', __('messages.folder.created'));
    }

    public function update(Request $request, Folder $folder, UserActivityLogger $logger): RedirectResponse
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

        if (! $user->canAccessDepartmentInFolderScope($departmentId)) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => __('messages.folder.department_denied')]);
        }

        $this->stripLocationFieldsUnlessAllowed($validated, $user, $folder);

        $oldValues = $folder->only(['name', 'department_id', 'parent_id', 'cabinet_number', 'row_number', 'box_number', 'is_active']);

        $folder->update([
            ...$validated,
            'department_id' => $departmentId,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $logger->logFolderUpdated(
            $user,
            $folder,
            $oldValues,
            $folder->only(['name', 'department_id', 'parent_id', 'cabinet_number', 'row_number', 'box_number', 'is_active']),
            $request,
        );

        return redirect()
            ->route('settings.folders.index')
            ->with('success', __('messages.folder.updated'));
    }

    public function destroy(Request $request, Folder $folder, UserActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeFolderAccess($user, $folder);

        $logger->logFolderDeleted($user, $folder, $request);
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
        $ids = $user->folderOrgScopeDepartmentIds();

        if ($ids !== null) {
            $ids = array_map(intval(...), $ids);
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array((int) $option['id'], $ids, true)
            ));
        }

        return $options;
    }
}
