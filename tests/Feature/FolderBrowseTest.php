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

class FolderBrowseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_sidebar_has_a_folder_list_option_instead_of_documents_or_folder_names(): void
    {
        $department = $this->department('ARC');
        $user = $this->userIn($department, ['transactions.view', 'transactions.create', 'profile.view']);
        $root = $this->folder($department, 'عقود');
        $this->folder($department, 'عقود 2026', $root);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $nav = $this->sidebarNav($response->getContent());

        $this->assertStringContainsString(route('folders.index'), $nav);
        $this->assertStringContainsString(__('nav.folders'), $nav);
        $this->assertStringNotContainsString('عقود', $nav);
        $this->assertStringNotContainsString(route('documents.index'), $nav);
        $this->assertStringNotContainsString(__('nav.documents'), $nav);
    }

    public function test_folder_list_shows_root_folders_only(): void
    {
        $department = $this->department('LIST');
        $user = $this->userIn($department, ['transactions.view', 'transactions.create']);
        $root = $this->folder($department, 'العقود');
        $child = $this->folder($department, 'عقود فرعية', $root);

        $this->actingAs($user)
            ->get(route('folders.index'))
            ->assertOk()
            ->assertSee(__('nav.folders'), false)
            ->assertSee(route('folders.browse', $root), false)
            ->assertSee('العقود', false)
            ->assertDontSee($child->name, false);
    }

    public function test_opening_a_folder_shows_inner_folders_and_only_its_transactions(): void
    {
        $department = $this->department('ARC2');
        $user = $this->userIn($department, ['transactions.view', 'transactions.create']);
        $root = $this->folder($department, 'المالية');
        $child = $this->folder($department, 'الفواتير', $root);
        $own = $this->transaction($department, $user, $root, 'معاملة المجلد الخارجي');
        $inner = $this->transaction($department, $user, $child, 'معاملة المجلد الداخلي');

        $this->actingAs($user)
            ->get(route('folders.browse', $root))
            ->assertOk()
            ->assertSee(route('folders.browse', $child), false)
            ->assertSee('الفواتير', false)
            ->assertSee($own->title, false)
            ->assertDontSee($inner->title, false);

        $this->actingAs($user)
            ->get(route('folders.browse', $child))
            ->assertOk()
            ->assertSee($inner->title, false)
            ->assertDontSee($own->title, false)
            ->assertSee(__('folders.transactions_title'), false);
    }

    public function test_folder_outside_the_user_scope_is_forbidden(): void
    {
        $department = $this->department('OWN');
        $other = $this->department('OTHER');
        $user = $this->userIn($department, ['transactions.view', 'transactions.create']);
        $folder = $this->folder($other, 'مجلد خارج النطاق');

        $this->actingAs($user)
            ->get(route('folders.browse', $folder))
            ->assertForbidden();
    }

    public function test_user_without_transaction_access_does_not_see_folders_in_the_sidebar(): void
    {
        $department = $this->department('HR');
        $user = $this->userIn($department, ['profile.view']);
        $folder = $this->folder($department, 'شؤون الموظفين');

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $nav = $this->sidebarNav($response->getContent());

        $this->assertStringNotContainsString($folder->name, $nav);
        $this->assertStringNotContainsString(route('folders.index'), $nav);
        $this->assertStringNotContainsString(route('documents.index'), $nav);
    }

    private function sidebarNav(string $html): string
    {
        $this->assertSame(1, preg_match('/<nav class="navbar-menu sidebar-nav"[\s\S]*?<\/nav>/', $html, $matches));

        return $matches[0];
    }

    private function department(string $code): Department
    {
        return Department::create([
            'name' => "Department {$code}",
            'unit_label' => 'Unit',
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);
    }

    /** @param list<string> $permissions */
    private function userIn(Department $department, array $permissions): User
    {
        $role = Role::create([
            'name' => 'Folder browse '.Str::random(6),
            'slug' => 'folder-browse-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
            'view_descendant_units' => false,
        ]);
    }

    private function folder(Department $department, string $name, ?Folder $parent = null): Folder
    {
        return Folder::create([
            'name' => $name,
            'parent_id' => $parent?->id,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    private function transaction(Department $department, User $creator, Folder $folder, string $title): Transaction
    {
        $reference = Str::upper(Str::random(8));

        return Transaction::create([
            'reference_number' => "TX-{$reference}",
            'archival_reference' => "AR-{$reference}",
            'title' => $title,
            'department_id' => $department->id,
            'folder_id' => $folder->id,
            'transaction_status_id' => TransactionStatus::where('is_initial', true)->value('id'),
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }
}
