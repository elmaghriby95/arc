<?php

namespace Tests\Feature;

use App\Enums\WorkflowAction;
use App\Models\Department;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use App\Notifications\TransactionStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_reviewer_is_notified_only_for_the_review_stage(): void
    {
        $department = $this->department('REV-NOTE');
        $review = TransactionStatus::where('code', 'REVIEW')->firstOrFail();
        $approved = TransactionStatus::where('code', 'APPROVED')->firstOrFail();
        $archived = TransactionStatus::where('is_final', true)->firstOrFail();

        $clerk = $this->userIn($department, ['transactions.view', 'transactions.create'], 'Clerk');
        $reviewer = $this->userIn($department, ['transactions.view', $review->required_permission], 'Reviewer');
        $approver = $this->userIn($department, ['transactions.view', $approved->required_permission], 'Approver');
        $head = $this->userIn($department, ['transactions.view'], 'Head');
        $department->update(['head_id' => $head->id]);

        $transaction = $this->transaction($department, $clerk, TransactionStatus::where('is_initial', true)->firstOrFail());

        $this->move($transaction, $clerk, $review, WorkflowAction::Submit);

        $this->assertCount(1, $reviewer->notifications);
        $this->assertSame($review->name, $reviewer->notifications()->first()->data['to_status']);
        $this->assertCount(0, $clerk->notifications);
        $this->assertCount(0, $approver->notifications);

        $this->move($transaction, $reviewer, $approved, WorkflowAction::Approve);

        $reviewer->refresh();
        $approver->refresh();
        $clerk->refresh();

        $this->assertCount(1, $reviewer->notifications);
        $this->assertSame($review->name, $reviewer->notifications()->first()->data['to_status']);
        $this->assertCount(1, $approver->notifications);
        $this->assertSame($approved->name, $approver->notifications()->first()->data['to_status']);
        $this->assertCount(1, $clerk->notifications);
        $this->assertSame($approved->name, $clerk->notifications()->first()->data['to_status']);

        $this->move($transaction, $approver, $archived, WorkflowAction::Approve);

        $reviewer->refresh();
        $clerk->refresh();
        $head->refresh();

        $this->assertCount(1, $reviewer->notifications);
        $this->assertTrue($clerk->notifications->contains(
            fn ($notification) => ($notification->data['to_status'] ?? null) === $archived->name
        ));
        $this->assertTrue($head->notifications->contains(
            fn ($notification) => ($notification->data['to_status'] ?? null) === $archived->name
        ));
        $this->assertFalse($reviewer->notifications->contains(
            fn ($notification) => ($notification->data['to_status'] ?? null) === $approved->name
        ));
    }

    public function test_navbar_hides_notifications_for_stages_the_user_does_not_handle(): void
    {
        $department = $this->department('NAV-NOTE');
        $review = TransactionStatus::where('code', 'REVIEW')->firstOrFail();
        $approved = TransactionStatus::where('code', 'APPROVED')->firstOrFail();
        $reviewer = $this->userIn($department, ['transactions.view', $review->required_permission], 'Navbar reviewer');
        $clerk = $this->userIn($department, ['transactions.view', 'transactions.create'], 'Navbar clerk');
        $transaction = $this->transaction($department, $clerk, $review);

        $this->move($transaction, $clerk, $review, WorkflowAction::Submit);

        $reviewMessage = $reviewer->notifications()->first()->data['message'];

        $reviewer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => TransactionStatusChanged::class,
            'data' => [
                'type' => 'transaction_status_changed',
                'message' => 'SHOULD-NOT-SEE-OTHER-STAGE',
                'transaction_id' => $transaction->id,
                'to_status' => $approved->name,
            ],
        ]);

        $response = $this->actingAs($reviewer)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee($reviewMessage, false)
            ->assertDontSee('SHOULD-NOT-SEE-OTHER-STAGE', false);
    }

    public function test_unit_scoped_stage_does_not_notify_another_department(): void
    {
        $department = $this->department('UNIT-A');
        $otherDepartment = $this->department('UNIT-B');
        $status = TransactionStatus::create([
            'name' => 'مراجعة الوحدة',
            'code' => 'UNITREV',
            'sort_order' => 50,
            'required_permission' => 'transactions.workflow.unitrev',
            'visibility_scope' => TransactionStatus::VISIBILITY_UNIT,
            'color' => '#0ea5e9',
            'is_initial' => false,
            'is_final' => false,
            'is_active' => true,
        ]);

        $clerk = $this->userIn($department, ['transactions.view', 'transactions.create'], 'Unit clerk');
        $localReviewer = $this->userIn($department, ['transactions.view', $status->required_permission], 'Local reviewer');
        $otherReviewer = $this->userIn($otherDepartment, ['transactions.view', $status->required_permission], 'Other reviewer');
        $transaction = $this->transaction($department, $clerk, TransactionStatus::where('is_initial', true)->firstOrFail());

        $this->move($transaction, $clerk, $status, WorkflowAction::Submit);

        $this->assertCount(1, $localReviewer->notifications);
        $this->assertCount(0, $otherReviewer->notifications);
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
    private function userIn(Department $department, array $permissions, string $name): User
    {
        $role = Role::create([
            'name' => $name.' '.Str::random(4),
            'slug' => 'notif-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'name' => $name,
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }

    private function transaction(Department $department, User $creator, TransactionStatus $status): Transaction
    {
        $reference = Str::upper(Str::random(8));

        return Transaction::create([
            'reference_number' => "TX-{$reference}",
            'title' => "Transaction {$reference}",
            'department_id' => $department->id,
            'transaction_status_id' => $status->id,
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }

    private function move(Transaction $transaction, User $actor, TransactionStatus $to, WorkflowAction $action): void
    {
        $fromId = $transaction->transaction_status_id;

        $transaction->update(['transaction_status_id' => $to->id]);

        $transaction->statusHistories()->create([
            'from_status_id' => $fromId,
            'to_status_id' => $to->id,
            'changed_by' => $actor->id,
            'action' => $action->value,
        ]);
    }
}
