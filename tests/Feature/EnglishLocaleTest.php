<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnglishLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_chosen_on_the_login_page_stays_active_after_sign_in(): void
    {
        [$arabic, $english] = $this->languages();
        $user = $this->user($arabic);

        $this->withSession(['locale' => 'en'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/dashboard');

        $this->assertSame($english->id, $user->fresh()->language_id);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('Dashboard', false)
            ->assertDontSee('لوحة التحكم', false);
    }

    public function test_authenticated_user_can_switch_the_interface_to_english(): void
    {
        [$arabic, $english] = $this->languages();
        $user = $this->user($arabic);

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->from('/profile')
            ->post(route('locale.switch', $english))
            ->assertRedirect('/profile')
            ->assertSessionHas('locale', 'en');

        $this->assertSame($english->id, $user->fresh()->language_id);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertSee('Dashboard', false)
            ->assertSee('Profile', false)
            ->assertDontSee('لوحة التحكم', false);
    }

    /** @return array{0: Language, 1: Language} */
    private function languages(): array
    {
        $arabic = Language::query()->create([
            'name' => 'العربية',
            'code' => 'ar',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'is_active' => true,
            'is_default' => true,
        ]);

        $english = Language::query()->create([
            'name' => 'English',
            'code' => 'en',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ]);

        return [$arabic, $english];
    }

    private function user(Language $language): User
    {
        $role = Role::query()->create([
            'name' => 'موظف',
            'slug' => 'employee-'.Str::random(6),
            'description' => 'دور للاختبار',
            'permissions' => Role::baselinePermissions(),
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'language_id' => $language->id,
        ]);
    }
}
