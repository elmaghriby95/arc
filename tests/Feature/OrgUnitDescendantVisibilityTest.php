<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrgUnitDescendantVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_employee_without_the_option_sees_only_their_own_unit(): void
    {
        [$sector, $administration, $section, $other] = $this->organization();
        $user = $this->employee($sector, viewDescendants: false);
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();

        $own = $this->transaction($sector, $user, $draft, 'Sector own transaction');
        $administrationTransaction = $this->transaction($administration, $user, $draft, 'Administration hidden transaction');
        $sectionTransaction = $this->transaction($section, $user, $draft, 'Section hidden transaction');
        $this->transaction($other, $user, $draft, 'Other sector transaction');

        $this->assertSame([$sector->id], $user->orgScopeDepartmentIds());

        $this->actingAs($user)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Sector own transaction')
            ->assertDontSee('Administration hidden transaction')
            ->assertDontSee('Section hidden transaction')
            ->assertDontSee('Other sector transaction')
            ->assertDontSee('Administration Beta')
            ->assertDontSee('Section Gamma');

        $this->actingAs($user)->get("/transactions/{$own->id}")->assertOk();
        $this->actingAs($user)->get("/transactions/{$administrationTransaction->id}")->assertForbidden();
        $this->actingAs($user)->get("/transactions/{$sectionTransaction->id}")->assertForbidden();
    }

    public function test_employee_with_the_option_sees_units_under_their_own(): void
    {
        [$sector, $administration, $section, $other] = $this->organization();
        $user = $this->employee($sector, viewDescendants: true);
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();

        $this->transaction($sector, $user, $draft, 'Sector visible transaction');
        $administrationTransaction = $this->transaction($administration, $user, $draft, 'Administration visible transaction');
        $sectionTransaction = $this->transaction($section, $user, $draft, 'Section visible transaction');
        $otherTransaction = $this->transaction($other, $user, $draft, 'Outside sector transaction');

        $this->assertEqualsCanonicalizing(
            [$sector->id, $administration->id, $section->id],
            $user->orgScopeDepartmentIds(),
        );

        $this->actingAs($user)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Sector visible transaction')
            ->assertSee('Administration visible transaction')
            ->assertSee('Section visible transaction')
            ->assertDontSee('Outside sector transaction')
            ->assertSee('Administration Beta')
            ->assertSee('Section Gamma');

        $this->actingAs($user)->get("/transactions/{$administrationTransaction->id}")->assertOk();
        $this->actingAs($user)->get("/transactions/{$sectionTransaction->id}")->assertOk();
        $this->actingAs($user)->get("/transactions/{$otherTransaction->id}")->assertForbidden();
    }

    public function test_view_all_and_admin_still_see_every_unit_when_the_option_is_off(): void
    {
        [$sector, $administration] = $this->organization();
        $draft = TransactionStatus::where('is_initial', true)->firstOrFail();
        $viewer = $this->employee($sector, viewDescendants: false, permissions: [
            'transactions.view',
            'transactions.view-all',
        ]);
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::SUPER_ADMIN_SLUG)->value('id'),
            'department_id' => $sector->id,
            'view_descendant_units' => false,
        ]);

        $this->transaction($administration, $viewer, $draft, 'Descendant for privileged users');

        $this->assertNull($admin->orgScopeDepartmentIds());

        $this->actingAs($viewer)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Descendant for privileged users');

        $this->actingAs($admin)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Descendant for privileged users');
    }

    public function test_user_form_saves_the_descendant_option(): void
    {
        $actor = $this->employee(department: null, viewDescendants: true, permissions: [
            'settings.users.create',
            'settings.users.edit',
        ]);
        $role = Role::where('slug', 'user')->firstOrFail();
        $sector = Department::create([
            'name' => 'Sector Alpha',
            'unit_label' => 'قطاع',
            'code' => 'SEC-FORM',
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->get('/settings/users/create')
            ->assertOk()
            ->assertSee('name="view_descendant_units" value="1" checked', false);

        $this->actingAs($actor)->post('/settings/users', [
            'name' => 'Scoped Employee',
            'email' => 'scoped.employee@example.com',
            'employee_number' => 'EMP-SCOPE-001',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role_id' => $role->id,
            'department_id' => $sector->id,
            'view_descendant_units' => '1',
        ])->assertRedirect('/settings/users');

        $created = User::where('email', 'scoped.employee@example.com')->firstOrFail();
        $this->assertTrue($created->view_descendant_units);

        $this->actingAs($actor)
            ->get("/settings/users/{$created->id}/edit")
            ->assertOk()
            ->assertSee('name="view_descendant_units" value="1" checked', false);

        $this->actingAs($actor)->put("/settings/users/{$created->id}", [
            'name' => $created->name,
            'email' => $created->email,
            'employee_number' => $created->employee_number,
            'role_id' => $role->id,
            'department_id' => $sector->id,
        ])->assertRedirect("/settings/users/{$created->id}/edit");

        $this->assertFalse($created->refresh()->view_descendant_units);

        $this->actingAs($actor)
            ->get("/settings/users/{$created->id}/edit")
            ->assertOk()
            ->assertDontSee('name="view_descendant_units" value="1" checked', false);
    }

    /** @return array{0: Department, 1: Department, 2: Department, 3: Department} */
    private function organization(): array
    {
        $sector = Department::create([
            'name' => 'Sector Alpha',
            'unit_label' => 'قطاع',
            'code' => 'SEC-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);
        $administration = Department::create([
            'name' => 'Administration Beta',
            'unit_label' => 'إدارة',
            'code' => 'ADM-'.Str::upper(Str::random(4)),
            'parent_id' => $sector->id,
            'is_active' => true,
        ]);
        $section = Department::create([
            'name' => 'Section Gamma',
            'unit_label' => 'قسم',
            'code' => 'SEC2-'.Str::upper(Str::random(4)),
            'parent_id' => $administration->id,
            'is_active' => true,
        ]);
        $other = Department::create([
            'name' => 'Sector Delta',
            'unit_label' => 'قطاع',
            'code' => 'OTH-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        return [$sector, $administration, $section, $other];
    }

    /** @param list<string> $permissions */
    private function employee(?Department $department, bool $viewDescendants, array $permissions = ['transactions.view', 'transactions.create']): User
    {
        $role = Role::create([
            'name' => 'Scope test '.Str::random(6),
            'slug' => 'scope-test-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department?->id,
            'view_descendant_units' => $viewDescendants,
        ]);
    }

    private function transaction(Department $department, User $creator, TransactionStatus $status, string $title): Transaction
    {
        $reference = Str::upper(Str::random(10));

        return Transaction::create([
            'reference_number' => "TX-{$reference}",
            'archival_reference' => "AR-{$reference}",
            'title' => $title,
            'department_id' => $department->id,
            'transaction_status_id' => $status->id,
            'created_by' => $creator->id,
            'transaction_date' => now()->toDateString(),
        ]);
    }
}
