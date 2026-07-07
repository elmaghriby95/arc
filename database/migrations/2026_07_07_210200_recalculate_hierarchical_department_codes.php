<?php

use App\Models\Department;
use App\Services\DepartmentCodeService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(DepartmentCodeService::class);
        $service->recalculateAll();
    }

    public function down(): void
    {
        // Hierarchical codes replace prior slug-based codes; not reverted automatically.
    }
};
