<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserPermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_edit_page_lists_permissions_for_one_user(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->get("/settings/users/{$target->id}/edit")
            ->assertOk()
            ->assertSee('data-user-permissions', false)
            ->assertSee('name="permissions[]"', false)
            ->assertSee(Permission::DocumentsView->value, false);
    }

    public function test_admin_can_grant_and_revoke_a_permission_for_one_user(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $role = $this->roleWith([
            Permission::TransactionsView->value,
            Permission::DocumentsView->value,
        ]);
        $target = User::factory()->create(['role_id' => $role->id]);
        $colleague = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($target)->get('/documents')->assertOk();

        $base = $role->fresh()->permissions;
        $desired = array_values(array_diff($base, [Permission::DocumentsView->value]));
        $desired[] = Permission::ReportsView->value;

        $this->actingAs($actor)
            ->put("/settings/users/{$target->id}", $this->payload($target, $desired))
            ->assertRedirect("/settings/users/{$target->id}/edit");

        $target->refresh();

        $this->assertTrue($target->hasPermission(Permission::ReportsView->value));
        $this->assertFalse($target->hasPermission(Permission::DocumentsView->value));
        $this->assertTrue($target->hasPermission(Permission::TransactionsView->value));
        $this->assertSame([Permission::ReportsView->value], $target->granted_permissions);
        $this->assertSame([Permission::DocumentsView->value], $target->revoked_permissions);
        $this->assertSame($role->id, $target->role_id);

        $colleague->refresh();
        $this->assertFalse($colleague->hasPermission(Permission::ReportsView->value));
        $this->assertTrue($colleague->hasPermission(Permission::DocumentsView->value));

        $this->actingAs($target)->get('/documents')->assertForbidden();
        $this->actingAs($colleague)->get('/documents')->assertOk();
    }

    public function test_saving_user_without_permission_form_keeps_existing_overrides(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $target = User::factory()->create();
        $target->granted_permissions = [Permission::DocumentsView->value];
        $target->revoked_permissions = [Permission::DashboardView->value];
        $target->save();

        $this->actingAs($actor)
            ->put("/settings/users/{$target->id}", [
                'name' => 'Updated Name',
                'email' => $target->email,
                'employee_number' => $target->employee_number,
                'role_id' => $target->role_id,
                'department_id' => $target->department_id,
                'language_id' => $target->language_id,
                'view_descendant_units' => $target->view_descendant_units ? '1' : '0',
            ])
            ->assertRedirect("/settings/users/{$target->id}/edit");

        $target->refresh();

        $this->assertSame('Updated Name', $target->name);
        $this->assertSame([Permission::DocumentsView->value], $target->granted_permissions);
        $this->assertSame([Permission::DashboardView->value], $target->revoked_permissions);
        $this->assertTrue($target->hasPermission(Permission::DocumentsView->value));
        $this->assertFalse($target->hasPermission(Permission::DashboardView->value));
    }

    public function test_changing_role_replaces_previous_user_overrides(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $currentRole = $this->roleWith([Permission::TransactionsView->value]);
        $nextRole = $this->roleWith([Permission::DocumentsView->value]);
        $target = User::factory()->create(['role_id' => $currentRole->id]);
        $target->granted_permissions = [Permission::DocumentsPrint->value];
        $target->save();

        $this->actingAs($actor)
            ->put("/settings/users/{$target->id}", $this->payload($target, $nextRole->fresh()->permissions, $nextRole->id))
            ->assertRedirect("/settings/users/{$target->id}/edit");

        $target->refresh();

        $this->assertSame($nextRole->id, $target->role_id);
        $this->assertNull($target->granted_permissions);
        $this->assertNull($target->revoked_permissions);
        $this->assertTrue($target->hasPermission(Permission::DocumentsView->value));
        $this->assertFalse($target->hasPermission(Permission::DocumentsPrint->value));
        $this->assertFalse($target->hasPermission(Permission::TransactionsView->value));
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->from("/settings/users/{$target->id}/edit")
            ->put("/settings/users/{$target->id}", $this->payload($target, ['not-a-real-permission']))
            ->assertRedirect("/settings/users/{$target->id}/edit")
            ->assertSessionHasErrors('permissions.0');

        $this->assertNull($target->fresh()->granted_permissions);
    }

    public function test_user_cannot_remove_their_own_user_edit_permission(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);

        $this->actingAs($actor)
            ->from("/settings/users/{$actor->id}/edit")
            ->put("/settings/users/{$actor->id}", $this->payload($actor, [Permission::DashboardView->value]))
            ->assertRedirect("/settings/users/{$actor->id}/edit")
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($actor->fresh()->hasPermission(Permission::SettingsUsersEdit->value));
    }

    public function test_non_admin_cannot_override_super_admin_permissions(): void
    {
        $actor = $this->userWithPermissions([Permission::SettingsUsersEdit->value]);
        $adminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail();
        $target = User::factory()->create(['role_id' => $adminRole->id]);

        $this->assertTrue($target->fresh()->isAdmin());
        $this->assertFalse($target->fresh()->permissionsCanBeCustomizedBy($actor));

        $this->actingAs($actor)
            ->get("/settings/users/{$target->id}/edit")
            ->assertOk()
            ->assertDontSee('name="permissions_submitted"', false);

        $this->actingAs($actor)
            ->put("/settings/users/{$target->id}", $this->payload($target, [Permission::DashboardView->value]))
            ->assertRedirect("/settings/users/{$target->id}/edit");

        $target->refresh();

        $this->assertNull($target->granted_permissions);
        $this->assertNull($target->revoked_permissions);
        $this->assertTrue($target->hasPermission(Permission::SettingsUsersEdit->value));
        $this->assertTrue($target->hasPermission(Permission::DocumentsView->value));
    }

    /** @param list<string> $permissions */
    private function roleWith(array $permissions): Role
    {
        return Role::create([
            'name' => 'Permission override '.Str::random(6),
            'slug' => 'permission-override-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        return User::factory()->create([
            'role_id' => $this->roleWith($permissions)->id,
        ]);
    }

    /** @param  list<string>  $permissions */
    private function payload(User $target, array $permissions, ?int $roleId = null): array
    {
        return [
            'name' => $target->name,
            'email' => $target->email,
            'employee_number' => $target->employee_number,
            'role_id' => $roleId ?? $target->role_id,
            'department_id' => $target->department_id,
            'language_id' => $target->language_id,
            'view_descendant_units' => $target->view_descendant_units ? '1' : '0',
            'permissions_submitted' => '1',
            'permissions' => $permissions,
        ];
    }
}
