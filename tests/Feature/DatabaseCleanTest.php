<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Language;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\TransactionType;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\User;
use App\Services\DatabaseCleanBootstrap;
use App\Services\DatabaseCleanService;
use App\Support\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DatabaseCleanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_database_clean_permission_is_super_admin_only_and_not_assignable(): void
    {
        $this->assertContains(Permission::SettingsDatabaseClean->value, Permission::superAdminOnlyValues());
        $this->assertNotContains(Permission::SettingsDatabaseClean->value, PermissionRegistry::assignableValues());

        $adminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail();
        $adminRole->save();

        $this->assertTrue($adminRole->fresh()->hasPermission(Permission::SettingsDatabaseClean->value));

        $userRole = Role::where('slug', '!=', Role::SUPER_ADMIN_SLUG)->firstOrFail();
        $userRole->update([
            'permissions' => array_merge($userRole->permissions ?? [], [Permission::SettingsDatabaseClean->value]),
        ]);

        $this->assertFalse($userRole->fresh()->hasPermission(Permission::SettingsDatabaseClean->value));
    }

    public function test_non_admin_cannot_access_database_clean(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', '!=', Role::SUPER_ADMIN_SLUG)->value('id'),
        ]);

        $this->actingAs($user)
            ->get(route('settings.database-clean.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_clean_operational_data_while_preserving_core_settings(): void
    {
        $admin = $this->adminUser(['password' => Hash::make('password')]);
        $staff = User::factory()->create([
            'role_id' => Role::where('slug', '!=', Role::SUPER_ADMIN_SLUG)->value('id'),
        ]);

        $department = Department::query()->create([
            'name' => 'إدارة تجريبية',
            'code' => 'TST',
            'is_active' => true,
        ]);

        $staff->update(['department_id' => $department->id]);

        Folder::query()->create([
            'name' => 'مجلد تجريبي',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        DocumentType::query()->create([
            'name' => 'خطاب',
            'code' => 'LTR',
            'is_active' => true,
        ]);

        TransactionType::query()->create([
            'name' => 'وارد',
            'code' => 'IN',
            'is_active' => true,
        ]);

        $settings = SystemSetting::instance();
        $settings->update(['app_name' => 'منظومة محفوظة']);

        $language = Language::query()->first() ?? Language::query()->create([
            'name' => 'Arabic',
            'code' => 'ar',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'is_active' => true,
            'is_default' => true,
        ]);
        $translationKey = TranslationKey::query()->firstOrCreate([
            'group' => 'messages',
            'key' => 'database_clean.test_key',
        ]);
        Translation::query()->updateOrCreate(
            ['translation_key_id' => $translationKey->id, 'language_id' => $language->id],
            ['value' => 'قيمة محفوظة'],
        );

        $translationCountBefore = Translation::query()->count();
        $languageCountBefore = Language::query()->count();

        $this->actingAs($admin)
            ->delete(route('settings.database-clean.destroy'), [
                'confirmation' => DatabaseCleanService::CONFIRMATION_PHRASE,
                'password' => 'password',
            ])
            ->assertRedirect(route('settings.database-clean.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('departments', 0);
        $this->assertDatabaseCount('folders', 0);
        $this->assertDatabaseCount('document_types', 0);
        $this->assertDatabaseCount('transaction_types', 0);

        $this->assertNotNull($admin->fresh());
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertNotNull($staff->fresh());
        $this->assertNull($staff->fresh()->department_id);

        $this->assertSame('منظومة محفوظة', SystemSetting::instance()->fresh()->app_name);
        $this->assertSame($translationCountBefore, Translation::query()->count());
        $this->assertSame($languageCountBefore, Language::query()->count());
        $this->assertDatabaseHas('translations', [
            'translation_key_id' => $translationKey->id,
            'value' => 'قيمة محفوظة',
        ]);
    }

    public function test_bootstrap_enables_feature_without_manual_migration(): void
    {
        $admin = $this->adminUser();

        Cache::forget('app.bootstrap.database_clean.v1');

        DatabaseCleanBootstrap::ensure();

        $this->assertTrue(
            Role::where('slug', Role::SUPER_ADMIN_SLUG)->firstOrFail()
                ->hasPermission(Permission::SettingsDatabaseClean->value)
        );

        $this->actingAs($admin)
            ->get(route('settings.database-clean.index'))
            ->assertOk();
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
