<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_super_admin_role_always_has_all_permissions(): void
    {
        $adminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail();

        $adminRole->forceFill([
            'permissions' => [Permission::DashboardView->value],
            'is_system' => false,
        ])->save();

        $adminRole->refresh();

        $this->assertTrue($adminRole->is_system);
        $this->assertEqualsCanonicalizing(PermissionRegistry::allValues(), $adminRole->permissions);
    }

    public function test_super_admin_role_cannot_be_updated(): void
    {
        $actor = $this->adminUser();
        $adminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail();
        $originalName = $adminRole->name;

        $response = $this
            ->actingAs($actor)
            ->from('/settings/roles')
            ->put("/settings/roles/{$adminRole->id}", [
                'name' => 'Changed Admin',
                'description' => 'Changed description',
                'permissions' => [Permission::DashboardView->value],
            ]);

        $response
            ->assertRedirect('/settings/roles')
            ->assertSessionHas('error', __('messages.role.cannot_update_super_admin'));

        $this->assertSame($originalName, $adminRole->refresh()->name);
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $actor = $this->adminUser();
        $adminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail();

        $response = $this
            ->actingAs($actor)
            ->from('/settings/roles')
            ->delete("/settings/roles/{$adminRole->id}");

        $response
            ->assertRedirect('/settings/roles')
            ->assertSessionHas('error', __('messages.role.cannot_delete_super_admin'));

        $this->assertNotNull($adminRole->fresh());
    }

    public function test_super_admin_user_role_cannot_be_changed(): void
    {
        $actor = $this->adminUser();
        $target = $this->adminUser(['email' => 'target-admin@example.com']);
        $userRole = Role::where('slug', 'user')->firstOrFail();

        $response = $this
            ->actingAs($actor)
            ->from("/settings/users/{$target->id}/edit")
            ->put("/settings/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'employee_number' => $target->employee_number,
                'role_id' => $userRole->id,
                'department_id' => null,
                'language_id' => null,
            ]);

        $response
            ->assertRedirect("/settings/users/{$target->id}/edit")
            ->assertSessionHasErrors('role_id');

        $this->assertTrue($target->refresh()->isAdmin());
    }

    public function test_super_admin_user_cannot_be_deleted_from_user_management(): void
    {
        $actor = $this->adminUser();
        $target = $this->adminUser(['email' => 'target-admin@example.com']);

        $response = $this
            ->actingAs($actor)
            ->from("/settings/users/{$target->id}/edit")
            ->delete("/settings/users/{$target->id}");

        $response
            ->assertRedirect("/settings/users/{$target->id}/edit")
            ->assertSessionHasErrors('user');

        $this->assertNotNull($target->fresh());
    }

    public function test_super_admin_user_cannot_delete_own_profile(): void
    {
        $admin = $this->adminUser();

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh());
    }

    /** @param array<string, mixed> $attributes */
    private function adminUser(array $attributes = []): User
    {
        return User::factory()->create([
            ...$attributes,
            'role_id' => Role::where('slug', Role::SUPER_ADMIN_SLUG)->value('id'),
        ]);
    }
}
