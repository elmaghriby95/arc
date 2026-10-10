<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_workflow_sentences_replace_the_text_used_in_the_app(): void
    {
        [$language, $admin] = $this->editor();

        $approve = $this->sentence($language, 'workflow', 'action.approve', 'اعتماد');
        $submit = $this->sentence($language, 'workflow', 'label.submit_to', 'إرسال إلى :status');

        app()->setLocale('ar');
        $this->assertSame('اعتماد', __('workflow.action.approve'));
        app('translation.loader')->load('ar', 'workflow');

        $this->actingAs($admin)
            ->post(route('settings.languages.translations.update', $language), [
                'translations_json' => json_encode([
                    $approve->id => 'موافقة نهائية',
                    $submit->id => 'تحويل إلى :status',
                ], JSON_UNESCAPED_UNICODE),
            ])
            ->assertRedirect(route('settings.languages.translations', $language));

        $this->assertDatabaseHas('translations', [
            'translation_key_id' => $approve->id,
            'language_id' => $language->id,
            'value' => 'موافقة نهائية',
        ]);
        $this->assertDatabaseHas('translations', [
            'translation_key_id' => $submit->id,
            'language_id' => $language->id,
            'value' => 'تحويل إلى :status',
        ]);

        $lines = app('translation.loader')->load('ar', 'workflow');

        $this->assertSame('موافقة نهائية', $lines['action']['approve'] ?? null);
        $this->assertSame('تحويل إلى :status', $lines['label']['submit_to'] ?? null);
        app()->setLocale('ar');
        $this->assertSame('موافقة نهائية', __('workflow.action.approve'));
        $this->assertSame('تحويل إلى :status', __('workflow.label.submit_to'));
    }

    public function test_saved_login_words_replace_the_text_shown_by_the_system(): void
    {
        [$language, $admin] = $this->editor();

        SystemSetting::query()->create([
            'login_texts' => [
                'ar' => [
                    'login' => 'تسجيل الدخول',
                ],
            ],
        ]);

        $login = $this->sentence($language, 'auth', 'login', 'تسجيل الدخول');

        app()->setLocale('ar');
        $this->assertSame('تسجيل الدخول', __('auth.login'));
        $this->assertSame('تسجيل الدخول', SystemSetting::instance()->loginText('login', 'ar'));

        $this->actingAs($admin)
            ->post(route('settings.languages.translations.update', $language), [
                'translations_json' => json_encode([
                    $login->id => 'دخول المنظومة',
                ], JSON_UNESCAPED_UNICODE),
            ])
            ->assertRedirect(route('settings.languages.translations', $language));

        SystemSetting::clearCache();
        app()->setLocale('ar');

        $this->assertSame('دخول المنظومة', __('auth.login'));
        $this->assertSame('دخول المنظومة', SystemSetting::instance()->loginText('login', 'ar'));

        auth()->logout();

        $this->withSession(['locale' => 'ar'])
            ->get('/login')
            ->assertOk()
            ->assertSee('دخول المنظومة', false);
    }

    public function test_translation_page_does_not_nest_delete_forms_inside_the_save_form(): void
    {
        [$language, $admin] = $this->editor();
        $key = $this->sentence($language, 'workflow', 'action.approve', 'اعتماد');

        $html = $this->actingAs($admin)
            ->get(route('settings.languages.translations', $language))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<form[^>]*id="translations-form"[^>]*>(.*?)<\/form>/s', $html, $matches));
        $this->assertStringNotContainsString('<form', $matches[1]);
        $this->assertStringContainsString('name="translations_json"', $matches[1]);
        $this->assertStringContainsString('class="form-control"', $matches[1]);
        $this->assertStringContainsString('form="delete-translation-'.$key->id.'"', $html);
        $this->assertStringContainsString('id="delete-translation-'.$key->id.'"', $html);
    }

    /** @return array{0: Language, 1: User} */
    private function editor(): array
    {
        $language = Language::query()->create([
            'name' => 'العربية',
            'code' => 'ar',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'is_active' => true,
            'is_default' => true,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::SUPER_ADMIN_SLUG)->value('id'),
            'language_id' => $language->id,
        ]);

        return [$language, $admin];
    }

    private function sentence(Language $language, string $group, string $key, string $value): TranslationKey
    {
        $translationKey = TranslationKey::query()->create([
            'group' => $group,
            'key' => $key,
        ]);

        Translation::query()->create([
            'translation_key_id' => $translationKey->id,
            'language_id' => $language->id,
            'value' => $value,
        ]);

        return $translationKey;
    }
}
