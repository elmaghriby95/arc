<?php

namespace Tests\Feature;

use App\Enums\LendingRequestStatus;
use App\Models\Department;
use App\Models\LendingRequest;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_dashboard_counts_only_the_users_direct_department(): void
    {
        $department = $this->department('HQ');
        $childDepartment = $this->department('HQ-OPS', $department);
        $otherDepartment = $this->department('FIN');
        $role = $this->roleWith([
            'transactions.view',
            'transactions.create',
            'documents.view',
            'departments.view',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();

        $ownTransaction = $this->transactionWithAttachment($department, $user, $draft, 'Own department attachment 1');
        $this->transactionWithAttachment($department, $user, $draft, 'Own department attachment 2');
        $this->transactionWithAttachment($childDepartment, $user, $draft, 'Child department attachment');
        $this->transactionWithAttachment($otherDepartment, $user, $draft, 'Other department attachment');
        $this->lendingRequest($ownTransaction, $user);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertDontSeeText('Own department attachment 1')
            ->assertDontSeeText('Own department attachment 2')
            ->assertDontSeeText('Child department attachment')
            ->assertDontSeeText('Other department attachment')
            ->assertViewHas('statCards', function (array $cards) {
                $cards = collect($cards)->keyBy('key');

                return $cards->count() === 5
                    && $cards['transactions']['value'] === 2
                    && $cards['documents']['value'] === 2
                    && $cards['lending']['value'] === 1;
            });
    }

    public function test_dashboard_keeps_five_cards_but_hides_links_without_feature_permissions(): void
    {
        $department = $this->department('ARC');
        $role = $this->roleWith([]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();

        $this->transactionWithAttachment($department, $user, $draft, 'Permissionless attachment');

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertDontSeeText('Permissionless attachment')
            ->assertViewHas('statCards', function (array $cards) {
                return collect($cards)->count() === 5
                    && collect($cards)->every(fn (array $card) => $card['url'] === null);
            });
    }

    public function test_reviewer_sees_review_card_by_permission_scope(): void
    {
        $department = $this->department('REV');
        $otherDepartment = $this->department('OPS');
        $reviewStatus = TransactionStatus::where('code', 'REVIEW')->firstOrFail();
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();
        $role = $this->roleWith([$reviewStatus->required_permission]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);

        $this->transactionWithAttachment($department, $user, $reviewStatus, 'Direct review attachment');
        $this->transactionWithAttachment($department, $user, $draft, 'Direct draft attachment');
        $this->transactionWithAttachment($otherDepartment, $user, $reviewStatus, 'Other review attachment');

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertViewHas('statCards', function (array $cards) {
                $cards = collect($cards)->keyBy('key');

                return $cards->has('review')
                    && $cards->count() === 5
                    && $cards['review']['value'] === 2
                    && $cards['review']['url'] === null
                    && $cards['transactions']['value'] === 2
                    && $cards['documents']['value'] === 2;
            });
    }

    public function test_review_and_archive_cards_work_for_users_without_department(): void
    {
        $department = $this->department('NO-DEPT-A');
        $otherDepartment = $this->department('NO-DEPT-B');
        $reviewStatus = TransactionStatus::where('code', 'REVIEW')->firstOrFail();
        $archivedStatus = TransactionStatus::where('is_final', true)->firstOrFail();
        $role = $this->roleWith([
            $reviewStatus->required_permission,
            $archivedStatus->required_permission,
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'department_id' => null,
        ]);

        $this->transactionWithAttachment($department, $user, $reviewStatus, 'Review without department 1');
        $this->transactionWithAttachment($otherDepartment, $user, $reviewStatus, 'Review without department 2');
        $this->transactionWithAttachment($otherDepartment, $user, $archivedStatus, 'Archive without department');

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertViewHas('statCards', function (array $cards) {
                $cards = collect($cards)->keyBy('key');

                return $cards['transactions']['value'] === 0
                    && $cards['documents']['value'] === 0
                    && $cards['review']['value'] === 2
                    && $cards['archived']['value'] === 1;
            });
    }

    private function department(string $code, ?Department $parent = null): Department
    {
        return Department::create([
            'name' => "Department {$code}",
            'unit_label' => 'Unit',
            'code' => $code,
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
    }

    /** @param list<string|null> $permissions */
    private function roleWith(array $permissions): Role
    {
        return Role::create([
            'name' => 'Dashboard test role '.Str::random(6),
            'slug' => 'dashboard-test-'.Str::random(8),
            'permissions' => array_values(array_filter($permissions)),
            'is_system' => false,
        ]);
    }

    private function transactionWithAttachment(
        Department $department,
        User $creator,
        TransactionStatus $status,
        string $attachmentTitle,
    ): Transaction {
        $reference = Str::upper(Str::random(10));
        $transaction = Transaction::create([
            'reference_number' => "TX-{$reference}",
            'archival_reference' => "AR-{$reference}",
            'title' => "Transaction {$reference}",
            'department_id' => $department->id,
            'transaction_status_id' => $status->id,
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);

        TransactionAttachment::create([
            'transaction_id' => $transaction->id,
            'title' => $attachmentTitle,
            'file_path' => 'documents/test.pdf',
            'file_name' => "{$reference}.pdf",
            'original_name' => "{$reference}.pdf",
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $creator->id,
        ]);

        return $transaction;
    }

    private function lendingRequest(Transaction $transaction, User $requester): LendingRequest
    {
        return LendingRequest::create([
            'transaction_id' => $transaction->id,
            'requested_by' => $requester->id,
            'status' => LendingRequestStatus::PendingReview,
            'purpose' => 'Dashboard count test',
        ]);
    }
}
