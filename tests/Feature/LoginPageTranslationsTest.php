<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageTranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_english_login_page_shows_english_copy_even_when_only_arabic_texts_are_stored(): void
    {
        $this->languages();

        $settings = SystemSetting::instance();
        $settings->login_texts = [
            'ar' => [
                'welcome_back' => 'مرحباً بعودتك',
                'login_desc' => 'وصف عربي فقط',
                'email' => 'البريد الإلكتروني',
                'password' => 'كلمة المرور',
                'remember_me' => 'تذكرني',
                'forgot_password' => 'نسيت كلمة المرور؟',
                'login' => 'تسجيل الدخول',
                'brand_subtitle' => 'منصة عربية',
                'feature_1' => 'ميزة عربية',
                'feature_2' => 'ميزة عربية ثانية',
                'feature_3' => 'ميزة عربية ثالثة',
                'login_page_title' => 'تسجيل الدخول',
            ],
        ];
        $settings->save();
        SystemSetting::clearCache();

        $response = $this->withSession(['locale' => 'en'])->get('/login');

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('Welcome back', false);
        $response->assertSee('Sign in to access the dashboard and archive', false);
        $response->assertSee('>Email<', false);
        $response->assertSee('>Password<', false);
        $response->assertSee('Remember me', false);
        $response->assertSee('Forgot your password?', false);
        $response->assertSee('>Log in<', false);
        $response->assertSee('Integrated platform for secure document archiving', false);
        $response->assertSee('Central archiving for documents and correspondence', false);
        $response->assertSee('Fast search and smart classification', false);
        $response->assertSee('Version tracking and audit log', false);
        $response->assertSee('Show password', false);
        $response->assertDontSee('وصف عربي فقط', false);
        $response->assertDontSee('منصة عربية', false);
        $response->assertDontSee('ميزة عربية', false);
    }

    public function test_custom_english_login_text_overrides_the_default_translation(): void
    {
        $this->languages();

        $settings = SystemSetting::instance();
        $settings->login_texts = [
            'en' => [
                'welcome_back' => 'Custom English Welcome',
            ],
            'ar' => [
                'welcome_back' => 'مرحباً بعودتك',
            ],
        ];
        $settings->save();
        SystemSetting::clearCache();

        $this->withSession(['locale' => 'en'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Custom English Welcome', false)
            ->assertDontSee('Welcome back', false)
            ->assertDontSee('مرحباً بعودتك', false);
    }

    public function test_english_login_errors_are_translated(): void
    {
        $this->languages();

        $this->withSession(['locale' => 'en'])
            ->from('/login')
            ->post('/login', [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertInvalid([
                'email' => 'These credentials do not match our records.',
            ]);

        $this->withSession(['locale' => 'en'])
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => '',
            ])
            ->assertRedirect('/login')
            ->assertInvalid([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);

        $this->withSession(['locale' => 'en'])
            ->from('/login')
            ->post('/login', [
                'email' => 'not-an-email',
                'password' => 'secret',
            ])
            ->assertRedirect('/login')
            ->assertInvalid([
                'email' => 'The email field must be a valid email address.',
            ]);
    }

    public function test_arabic_login_page_keeps_arabic_copy(): void
    {
        $this->languages();

        $this->withSession(['locale' => 'ar'])
            ->get('/login')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee('مرحباً بعودتك', false)
            ->assertSee('سجّل دخولك للوصول إلى لوحة التحكم وإدارة الأرشيف', false)
            ->assertSee('تسجيل الدخول', false)
            ->assertDontSee('Welcome back', false);
    }

    private function languages(): void
    {
        Language::query()->create([
            'name' => 'العربية',
            'code' => 'ar',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'is_active' => true,
            'is_default' => true,
        ]);

        Language::query()->create([
            'name' => 'English',
            'code' => 'en',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
