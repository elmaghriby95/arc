<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Folder;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class FolderClosureTest extends TestCase
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

    public function test_closing_a_folder_hides_it_from_transaction_creation(): void
    {
        $admin = $this->adminUser();
        $department = $this->department();
        $open = $this->folder($department, 'مجلد مفتوح');
        $full = $this->folder($department, 'مجلد ممتلئ');
        $this->transaction($department, $admin, $full);

        $this->actingAs($admin)
            ->patch(route('settings.folders.closure', $full))
            ->assertRedirect(route('settings.folders.index'))
            ->assertSessionHas('success', __('messages.folder.closed'));

        $this->assertTrue($full->fresh()->is_closed);

        $this->actingAs($admin)
            ->get(route('settings.folders.index'))
            ->assertOk()
            ->assertSee('مجلد ممتلئ', false)
            ->assertSee(__('settings.folders.closed_badge'), false)
            ->assertSee(__('settings.folders.reopen_short'), false);

        $this->actingAs($admin)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('data-folder-name="مجلد مفتوح"', false)
            ->assertDontSee('data-folder-name="مجلد ممتلئ"', false);

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'title' => 'معاملة على مجلد مغلق',
                'archival_reference' => 'AR-CLOSED-1',
                'department_id' => $department->id,
                'folder_id' => $full->id,
                'files' => [UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf')],
            ])
            ->assertSessionHasErrors('folder_id');

        $this->assertDatabaseMissing('transactions', ['archival_reference' => 'AR-CLOSED-1']);

        $this->actingAs($admin)
            ->patch(route('settings.folders.closure', $full))
            ->assertSessionHas('success', __('messages.folder.reopened'));

        $this->assertFalse($full->fresh()->is_closed);

        $this->actingAs($admin)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('data-folder-name="مجلد ممتلئ"', false)
            ->assertSee('data-folder-id="'.$open->id.'"', false);
    }

    public function test_open_child_of_a_closed_folder_stays_available(): void
    {
        $department = $this->department();
        $parent = $this->folder($department, 'مجلد أب مغلق');
        $child = Folder::create([
            'name' => 'مجلد فرعي مفتوح',
            'department_id' => $department->id,
            'parent_id' => $parent->id,
            'is_active' => true,
            'is_closed' => false,
        ]);
        $parent->update(['is_closed' => true]);

        $names = [];
        $walk = function ($nodes) use (&$walk, &$names): void {
            foreach ($nodes as $node) {
                $names[] = $node->name;
                $walk($node->children);
            }
        };
        $walk(Folder::scopedTree(null, activeOnly: true, excludeClosed: true));

        $this->assertNotContains('مجلد أب مغلق', $names);
        $this->assertContains('مجلد فرعي مفتوح', $names);
        $this->assertSame($child->id, Folder::query()->where('name', 'مجلد فرعي مفتوح')->value('id'));
    }

    public function test_existing_transaction_can_stay_in_a_closed_folder(): void
    {
        $admin = $this->adminUser();
        $department = $this->department();
        $folder = $this->folder($department, 'مجلد محفوظ');
        $otherClosed = $this->folder($department, 'مجلد مغلق آخر');
        $transaction = $this->transaction($department, $admin, $folder);
        $folder->update(['is_closed' => true]);
        $otherClosed->update(['is_closed' => true]);

        $this->actingAs($admin)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('مجلد محفوظ', false)
            ->assertDontSee('مجلد مغلق آخر', false);

        $this->actingAs($admin)
            ->put(route('transactions.update', $transaction), [
                'title' => 'عنوان محدّث',
                'archival_reference' => $transaction->archival_reference,
                'department_id' => $department->id,
                'folder_id' => $folder->id,
            ])
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame($folder->id, $transaction->fresh()->folder_id);

        $this->actingAs($admin)
            ->put(route('transactions.update', $transaction), [
                'title' => 'عنوان محدّث',
                'archival_reference' => $transaction->archival_reference,
                'department_id' => $department->id,
                'folder_id' => $otherClosed->id,
            ])
            ->assertSessionHasErrors('folder_id');
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

    private function folder(Department $department, string $name): Folder
    {
        return Folder::create([
            'name' => $name,
            'department_id' => $department->id,
            'is_active' => true,
            'is_closed' => false,
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
}
