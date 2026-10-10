<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_new_user_must_replace_the_temporary_password_before_using_the_system(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $login->assertRedirect(route('password.change', absolute: false));

        $this->get('/dashboard')->assertRedirect(route('password.change', absolute: false));

        $this->post('/logout')->assertRedirect(route('login', absolute: false));
        $this->assertGuest();
    }

    public function test_reused_password_is_rejected_and_a_new_one_unlocks_the_account(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password_changed_at' => null,
        ]);

        $this->actingAs($user)
            ->post('/password/change', [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->needsPasswordChange());

        $this->actingAs($user)
            ->post('/password/change', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('new-password', $user->password));

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_password_must_be_changed_again_after_thirty_days(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now()->subDays(30),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('password.change', absolute: false));

        $this->actingAs($user)
            ->get('/password/change')
            ->assertOk()
            ->assertSee(__('auth.password_change_expired'), false);
    }

    public function test_password_younger_than_thirty_days_does_not_force_a_change(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now()->subDays(29),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_admin_password_reset_forces_the_employee_to_choose_a_new_password(): void
    {
        $actor = $this->userWithPermissions(['settings.users.edit']);
        $target = User::factory()->create([
            'employee_number' => 'EMP-PWD-001',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $this->actingAs($actor)->put("/settings/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'employee_number' => $target->employee_number,
            'role_id' => $target->role_id,
            'department_id' => null,
            'language_id' => null,
            'password' => 'Temporary123!',
            'password_confirmation' => 'Temporary123!',
        ])->assertRedirect("/settings/users/{$target->id}/edit");

        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertNull($target->password_changed_at);
        $this->assertTrue(Hash::check('Temporary123!', $target->password));
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create([
            'name' => 'Password policy '.Str::random(6),
            'slug' => 'password-policy-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }
}
