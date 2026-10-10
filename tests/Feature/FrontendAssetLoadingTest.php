<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class FrontendAssetLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
    }

    public function test_authenticated_pages_do_not_load_shell_assets_from_public(): void
    {
        $user = $this->userWith([
            'dashboard.view',
            'reports.view',
            'lending-requests.view',
        ]);

        $dashboard = $this->actingAs($user)->get('/dashboard');

        $dashboard->assertOk();
        $dashboard->assertSee('[data-modal-open]', false);
        $dashboard->assertDontSee('/css/app.css', false);
        $dashboard->assertDontSee('/js/app.js', false);
        $dashboard->assertDontSee('org-unit-picker.js', false);

        $reports = $this->actingAs($user)->get('/reports');

        $reports->assertOk();
        $reports->assertSee('width="24"', false);
        $reports->assertSee('reports-module-icon', false);
        $reports->assertDontSee('/css/app.css', false);
        $reports->assertDontSee('org-unit-picker.js', false);
        $reports->assertDontSee('/icons/', false);

        $report = $this->actingAs($user)->get('/reports/transaction-pipeline');

        $report->assertOk();
        $report->assertSee('data-org-picker', false);
        $report->assertSee('orgReady', false);
        $report->assertDontSee('org-unit-picker.js', false);
        $report->assertDontSee('/js/', false);
    }

    /** @param list<string> $permissions */
    private function userWith(array $permissions): User
    {
        $department = Department::create([
            'name' => 'Assets department',
            'unit_label' => 'Unit',
            'code' => 'AST-'.Str::upper(Str::random(4)),
            'is_active' => true,
        ]);

        $role = Role::create([
            'name' => 'Assets role '.Str::random(4),
            'slug' => 'assets-'.Str::random(8),
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => $department->id,
        ]);
    }
}
