<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_empty_unit_can_be_deleted_without_changing_the_rest_of_the_tree(): void
    {
        $admin = $this->adminUser();
        $parent = $this->department('الإدارة العامة');
        $mistake = $this->department('إدارة بالاسم الخطأ', $parent);
        $sibling = $this->department('إدارة سليمة', $parent);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $mistake))
            ->assertRedirect(route('settings.organization.index'))
            ->assertSessionHas('success', __('messages.organization.deleted'));

        $this->assertNull($mistake->fresh());
        $this->assertSame($parent->id, $sibling->fresh()->parent_id);
        $this->assertNotNull($parent->fresh());
    }

    public function test_unit_with_a_child_cannot_be_deleted_and_the_child_stays_in_place(): void
    {
        $admin = $this->adminUser();
        $parent = $this->department('إدارة');
        $child = $this->department('قسم', $parent);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $parent))
            ->assertRedirect(route('settings.organization.index'))
            ->assertSessionHas('error', $this->blockedMessage('children', 1));

        $this->assertNotNull($parent->fresh());
        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_unit_with_a_folder_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $department = $this->department('إدارة العقود');
        $folder = Folder::create([
            'name' => 'مجلد العقود',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $department))
            ->assertRedirect(route('settings.organization.index'))
            ->assertSessionHas('error', $this->blockedMessage('folders', 1));

        $this->assertNotNull($department->fresh());
        $this->assertSame($department->id, $folder->fresh()->department_id);
    }

    public function test_unit_with_a_transaction_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $department = $this->department('إدارة المعاملات');
        $transaction = $this->transaction($department, $admin);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $department))
            ->assertRedirect(route('settings.organization.index'))
            ->assertSessionHas('error', $this->blockedMessage('transactions', 1));

        $this->assertNotNull($department->fresh());
        $this->assertSame($department->id, $transaction->fresh()->department_id);
    }

    public function test_unit_with_an_employee_or_document_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $withEmployee = $this->department('إدارة الموظفين');
        $employee = User::factory()->create(['department_id' => $withEmployee->id]);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $withEmployee))
            ->assertSessionHas('error', $this->blockedMessage('employees', 1));

        $this->assertSame($withEmployee->id, $employee->fresh()->department_id);

        $withDocument = $this->department('إدارة الوثائق');
        $document = Document::create([
            'reference_number' => 'DOC-'.Str::upper(Str::random(6)),
            'title' => 'وثيقة',
            'department_id' => $withDocument->id,
            'uploaded_by' => $admin->id,
            'file_path' => 'documents/sample.pdf',
            'file_name' => 'sample.pdf',
        ]);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $withDocument))
            ->assertSessionHas('error', $this->blockedMessage('documents', 1));

        $this->assertSame($withDocument->id, $document->fresh()->department_id);
    }

    public function test_blocked_delete_names_every_reason(): void
    {
        $admin = $this->adminUser();
        $department = $this->department('إدارة مرتبطة');
        $this->department('قسم تابع', $department);
        Folder::create([
            'name' => 'مجلد',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $reasons = implode(__('messages.organization.cannot_delete_reason.separator'), [
            __('messages.organization.cannot_delete_reason.children', ['count' => 1]),
            __('messages.organization.cannot_delete_reason.folders', ['count' => 1]),
        ]);

        $this->actingAs($admin)
            ->delete(route('settings.organization.destroy', $department))
            ->assertSessionHas('error', __('messages.organization.cannot_delete_because', [
                'reasons' => $reasons,
            ]));

        $this->assertNotNull($department->fresh());
    }

    public function test_tree_shows_delete_only_for_an_empty_unit_and_keeps_the_form_outside_the_edit_form(): void
    {
        $admin = $this->adminUser();
        $parent = $this->department('إدارة');
        $empty = $this->department('وحدة فارغة', $parent);

        $html = $this->actingAs($admin)
            ->get(route('settings.organization.index'))
            ->assertOk()
            ->assertSee($this->blockedMessage('children', 1), false)
            ->assertSee('id="org-delete-'.$empty->id.'"', false)
            ->getContent();

        $this->assertSame(1, preg_match('/<form[^>]*id="org-update-'.$empty->id.'"[^>]*>(.*?)<\/form>/s', $html, $matches));
        $this->assertStringNotContainsString('<form', $matches[1]);
        $this->assertStringContainsString('form="org-delete-'.$empty->id.'"', $matches[1]);
        $this->assertDoesNotMatchRegularExpression('/id="org-delete-'.$parent->id.'"/', $html);
    }

    private function blockedMessage(string $reason, int $count): string
    {
        return __('messages.organization.cannot_delete_because', [
            'reasons' => __('messages.organization.cannot_delete_reason.'.$reason, ['count' => $count]),
        ]);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::SUPER_ADMIN_SLUG)->value('id'),
        ]);
    }

    private function department(string $name, ?Department $parent = null): Department
    {
        return Department::create([
            'name' => $name,
            'unit_label' => 'إدارة',
            'code' => 'ORG-'.Str::upper(Str::random(4)),
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
    }

    private function transaction(Department $department, User $creator): Transaction
    {
        $reference = Str::upper(Str::random(8));

        return Transaction::create([
            'reference_number' => "TX-{$reference}",
            'archival_reference' => "AR-{$reference}",
            'title' => 'معاملة',
            'department_id' => $department->id,
            'transaction_status_id' => TransactionStatus::where('is_initial', true)->value('id'),
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }
};
