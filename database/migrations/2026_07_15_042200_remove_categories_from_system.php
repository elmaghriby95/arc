<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::all() as $role) {
            $role->update([
                'permissions' => Role::normalizePermissions($role->permissions),
            ]);
        }

        if (Schema::hasTable('documents') && Schema::hasColumn('documents', 'category_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Categories have been removed from the product and are not restored.
    }
};
