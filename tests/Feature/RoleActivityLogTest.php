<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Reports\SystemOperationsReportService;
use App\Support\Reports\ReportFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_renaming_a_role_is_written_to_the_operations_log(): void
    {
        $permissions = Role::baselinePermissions();
        $role = Role::create([
            'name' => 'موظف',
            'slug' => 'employee-rename-'.Str::random(6),
            'description' => 'دور الموظف',
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($admin)->put("/settings/roles/{$role->id}", [
            'name' => 'مدخل بيانات',
            'description' => 'دور الموظف',
            'permissions' => $permissions,
        ]);

        $response->assertRedirect(route('settings.roles.index'));
        $this->assertSame('مدخل بيانات', $role->refresh()->name);

        $log = AuditLog::query()->where('action', 'role.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('موظف', $log->old_values['name'] ?? null);
        $this->assertSame('مدخل بيانات', $log->new_values['name'] ?? null);
        $this->assertArrayNotHasKey('description', $log->old_values ?? []);

        app()->setLocale('ar');

        $data = app(SystemOperationsReportService::class)->generate(
            new ReportFilter(
                dateFrom: null,
                dateTo: null,
                departmentId: null,
                transactionTypeId: null,
                transactionStatusId: null,
                eventType: 'audit',
            ),
            $admin,
        );

        $event = $data['events']->first(fn (array $event) => ($event['meta']['action'] ?? null) === 'role.updated');

        $this->assertNotNull($event);
        $this->assertSame('تعديل دور', $event['label']);
        $this->assertStringContainsString('الاسم: موظف → مدخل بيانات', $event['details']);
    }

    public function test_renaming_a_transaction_type_is_written_to_the_operations_log(): void
    {
        $type = TransactionType::create([
            'name' => 'وارد',
            'code' => 'IN-'.Str::upper(Str::random(4)),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($admin)->put("/settings/transaction-types/{$type->id}", [
            'name' => 'صادر',
            'code' => $type->code,
            'description' => '',
            'sort_order' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('settings.transaction-types.index'));

        $log = AuditLog::query()->where('action', 'transaction_type.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('وارد', $log->old_values['name'] ?? null);
        $this->assertSame('صادر', $log->new_values['name'] ?? null);

        app()->setLocale('ar');

        $data = app(SystemOperationsReportService::class)->generate(
            new ReportFilter(
                dateFrom: null,
                dateTo: null,
                departmentId: null,
                transactionTypeId: $type->id,
                transactionStatusId: null,
                eventType: 'audit',
            ),
            $admin,
        );

        $event = $data['events']->first(fn (array $event) => ($event['meta']['action'] ?? null) === 'transaction_type.updated');

        $this->assertNotNull($event);
        $this->assertSame('تعديل نوع معاملة', $event['label']);
        $this->assertStringContainsString('وارد', $event['details']);
        $this->assertStringContainsString('صادر', $event['details']);
    }
}
