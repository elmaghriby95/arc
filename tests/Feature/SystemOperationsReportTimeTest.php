<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Reports\SystemOperationsReportService;
use App\Support\Reports\ReportFilter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemOperationsReportTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_operations_report_displays_events_in_display_timezone(): void
    {
        config()->set('app.timezone', 'Africa/Tripoli');
        config()->set('app.display_timezone', 'Africa/Tripoli');

        $actor = User::factory()->create();
        $eventTime = Carbon::parse('2026-07-15 12:00:00', 'Africa/Tripoli');

        $log = AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'admin.user.created',
            'auditable_type' => $actor->getMorphClass(),
            'auditable_id' => $actor->id,
            'new_values' => ['name' => $actor->name],
        ]);
        $log->forceFill([
            'created_at' => $eventTime,
            'updated_at' => $eventTime,
        ])->save();

        $data = app(SystemOperationsReportService::class)->generate(
            new ReportFilter(
                dateFrom: null,
                dateTo: null,
                departmentId: null,
                transactionTypeId: null,
                transactionStatusId: null,
                eventType: 'audit',
            ),
            $actor,
        );

        $this->assertSame('2026-07-15 12:00', $data['events']->first()['occurred_at_display']);
        $this->assertSame('2026-07-15 12:00:00', $data['events']->first()['occurred_at']);
    }
}
