<?php

namespace Tests\Feature;

use App\Enums\LendingRequestStatus;
use App\Models\Department;
use App\Models\LendingRequest;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use App\Services\LendingRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class LendingRequestTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_transferred_employee_does_not_see_previous_unit_lending_requests(): void
    {
        $previous = $this->department('PREV');
        $current = $this->department('NEXT');
        $employee = $this->userIn($previous, ['lending-requests.request', 'transactions.create']);
        $transaction = $this->transaction($previous, $employee, 'OLD-LEND-REF');
        $lendingRequest = app(LendingRequestService::class)->request(
            $employee,
            $transaction,
            'طلب من الإدارة السابقة',
            now()->addWeek()->toDateString(),
        );

        $this->assertNotNull($lendingRequest);
        $this->assertSame($previous->id, $lendingRequest->department_id);

        $employee->update(['department_id' => $current->id]);
        $employee->refresh();

        $currentTransaction = $this->transaction($current, $employee, 'NEW-LEND-REF');
        LendingRequest::create([
            'transaction_id' => $currentTransaction->id,
            'requested_by' => $employee->id,
            'department_id' => $current->id,
            'status' => LendingRequestStatus::PendingReview,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($employee)
            ->get(route('lending-requests.index'))
            ->assertOk()
            ->assertDontSee('OLD-LEND-REF')
            ->assertSee('NEW-LEND-REF');

        $this->actingAs($employee)
            ->get(route('lending-requests.show', $lendingRequest))
            ->assertForbidden();
    }

    public function test_previous_unit_keeps_the_request_and_the_new_unit_does_not(): void
    {
        $previous = $this->department('KEEP');
        $current = $this->department('HIDE');
        $employee = $this->userIn($previous, ['lending-requests.request']);
        $previousManager = $this->userIn($previous, ['lending-requests.view', 'transactions.create']);
        $currentManager = $this->userIn($current, ['lending-requests.view', 'transactions.create']);
        $transaction = $this->transaction($previous, $employee, 'STAYS-IN-PREV');

        $lendingRequest = LendingRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $employee->id,
            'department_id' => $previous->id,
            'status' => LendingRequestStatus::PendingReview,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $employee->update(['department_id' => $current->id]);

        $this->actingAs($previousManager)
            ->get(route('lending-requests.index'))
            ->assertOk()
            ->assertSee('STAYS-IN-PREV');

        $this->actingAs($previousManager)
            ->get(route('lending-requests.show', $lendingRequest))
            ->assertOk();

        $this->actingAs($currentManager)
            ->get(route('lending-requests.index'))
            ->assertOk()
            ->assertDontSee('STAYS-IN-PREV');

        $this->actingAs($currentManager)
            ->get(route('lending-requests.show', $lendingRequest))
            ->assertForbidden();
    }

    public function test_transferred_unit_manager_does_not_carry_personal_requests_into_the_new_unit(): void
    {
        $previous = $this->department('MGR-OLD');
        $current = $this->department('MGR-NEW');
        $manager = $this->userIn($previous, ['lending-requests.view', 'lending-requests.request', 'transactions.create']);
        $transaction = $this->transaction($previous, $manager, 'MGR-OLD-REF');

        LendingRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $manager->id,
            'department_id' => $previous->id,
            'status' => LendingRequestStatus::PendingReview,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $manager->update(['department_id' => $current->id]);

        $this->actingAs($manager->fresh())
            ->get(route('lending-requests.index'))
            ->assertOk()
            ->assertDontSee('MGR-OLD-REF');
    }

    private function department(string $code): Department
    {
        return Department::create([
            'name' => "Department {$code}",
            'unit_label' => 'Unit',
            'code' => $code,
            'is_active' => true,
        ]);
    }

    /** @param list<string> $permissions */
    private function userIn(Department $department, array $permissions): User
    {
        $role = Role::create([
            'name' => 'Lending transfer '.Str::random(6),
            'slug' => 'lending-transfer-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
            'view_descendant_units' => false,
        ]);
    }

    private function transaction(Department $department, User $creator, string $reference): Transaction
    {
        $approved = TransactionStatus::query()->where('code', 'APPROVED')->firstOrFail();

        return Transaction::create([
            'reference_number' => $reference,
            'archival_reference' => 'AR-'.$reference,
            'title' => "Transaction {$reference}",
            'department_id' => $department->id,
            'transaction_status_id' => $approved->id,
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }
};
