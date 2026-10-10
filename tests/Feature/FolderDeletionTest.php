<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class FolderDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $key = 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=';
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;

        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_folder_with_transactions_cannot_be_deleted(): void
    {
        $admin = $this->adminUser();
        $department = $this->department();
        $folder = $this->folder($department);
        $transaction = $this->transaction($department, $admin, $folder);

        $this->actingAs($admin)
            ->delete(route('settings.folders.destroy', $folder))
            ->assertRedirect(route('settings.folders.index'))
            ->assertSessionHas('error', __('messages.folder.cannot_delete_in_use'));

        $this->assertNotNull($folder->fresh());
        $this->assertSame($folder->id, $transaction->fresh()->folder_id);
    }

    public function test_empty_folder_can_be_deleted(): void
    {
        $admin = $this->adminUser();
        $folder = $this->folder($this->department());

        $this->actingAs($admin)
            ->delete(route('settings.folders.destroy', $folder))
            ->assertRedirect(route('settings.folders.index'))
            ->assertSessionHas('success', __('messages.folder.deleted'));

        $this->assertNull($folder->fresh());
    }

    public function test_folder_tree_shows_subfolder_form_with_parent_filled(): void
    {
        $admin = $this->adminUser();
        $department = $this->department();
        $folder = $this->folder($department);

        $this->actingAs($admin)
            ->get(route('settings.folders.index'))
            ->assertOk()
            ->assertSee('folder-subfolder-form', false)
            ->assertSee('name="add_context" value="tree-'.$folder->id.'"', false)
            ->assertSee('name="parent_id" value="'.$folder->id.'"', false)
            ->assertSee(__('settings.folders.add_sub_folder'), false)
            ->assertSee($folder->name, false);

        $this->actingAs($admin)
            ->post(route('settings.folders.store'), [
                'add_context' => 'tree-'.$folder->id,
                'name' => 'مجلد فرعي',
                'parent_id' => $folder->id,
                'department_id' => $department->id,
                'cabinet_number' => '1',
                'row_number' => '2',
                'box_number' => '3',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.folders.index'))
            ->assertSessionHas('success');

        $child = Folder::query()->where('name', 'مجلد فرعي')->first();
        $this->assertNotNull($child);
        $this->assertSame($folder->id, $child->parent_id);
    }

    public function test_model_delete_is_blocked_when_folder_has_transactions(): void
    {
        $admin = $this->adminUser();
        $department = $this->department();
        $folder = $this->folder($department);
        $this->transaction($department, $admin, $folder);

        $this->assertFalse($folder->delete());
        $this->assertNotNull($folder->fresh());
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::SUPER_ADMIN_SLUG)->value('id'),
        ]);
    }

    private function department(): Department
    {
        return Department::create([
            'name' => 'إدارة الأرشيف',
            'unit_label' => 'إدارة',
            'code' => 'ARC-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);
    }

    private function folder(Department $department): Folder
    {
        return Folder::create([
            'name' => 'مجلد العقود',
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    private function transaction(Department $department, User $creator, Folder $folder): Transaction
    {
        $reference = Str::upper(Str::random(8));

        return Transaction::create([
            'reference_number' => "TX-{$reference}",
            'archival_reference' => "AR-{$reference}",
            'title' => 'معاملة داخل المجلد',
            'department_id' => $department->id,
            'folder_id' => $folder->id,
            'transaction_status_id' => TransactionStatus::where('is_initial', true)->value('id'),
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }
};
