<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserRoleAssignmentPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_user_creation_always_uses_default_role(): void
    {
        $actor = $this->userWithPermissions(['settings.users.create']);
        $managerRole = Role::where('slug', 'manager')->firstOrFail();
        $defaultRole = Role::where('slug', 'user')->firstOrFail();

        $response = $this->actingAs($actor)->post('/settings/users', [
            'name' => 'Created Employee',
            'email' => 'created.employee@example.com',
            'employee_number' => 'EMP-ROLE-001',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $managerRole->id,
            'department_id' => null,
        ]);

        $response->assertRedirect('/settings/users');

        $created = User::where('email', 'created.employee@example.com')->firstOrFail();

        $this->assertSame($defaultRole->id, $created->role_id);
    }

    public function test_user_editing_keeps_current_role(): void
    {
        $actor = $this->userWithPermissions(['settings.users.edit']);
        $defaultRole = Role::where('slug', 'user')->firstOrFail();
        $managerRole = Role::where('slug', 'manager')->firstOrFail();
        $target = User::factory()->create([
            'role_id' => $defaultRole->id,
            'employee_number' => 'EMP-ROLE-002',
        ]);

        $response = $this->actingAs($actor)->put("/settings/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'employee_number' => $target->employee_number,
            'role_id' => $managerRole->id,
            'department_id' => null,
            'language_id' => null,
        ]);

        $response->assertRedirect("/settings/users/{$target->id}/edit");

        $this->assertSame($defaultRole->id, $target->refresh()->role_id);
    }

    public function test_role_select_is_not_rendered_on_user_forms(): void
    {
        $actor = $this->userWithPermissions(['settings.users.create', 'settings.users.edit']);
        $defaultRole = Role::where('slug', 'user')->firstOrFail();
        $target = User::factory()->create([
            'role_id' => $defaultRole->id,
            'employee_number' => 'EMP-ROLE-003',
        ]);

        $createResponse = $this->actingAs($actor)->get('/settings/users/create');
        $editResponse = $this->actingAs($actor)->get("/settings/users/{$target->id}/edit");

        $createResponse
            ->assertOk()
            ->assertDontSee('id="role_id"', false);

        $editResponse
            ->assertOk()
            ->assertDontSee('id="role_id"', false);
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Role assignment test '.Str::random(6),
            'slug' => 'role-assignment-test-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }
}
