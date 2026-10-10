<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceDepartmentDeleteRule('departments', 'parent_id', 'restrict');
        $this->replaceDepartmentDeleteRule('folders', 'department_id', 'restrict');
        $this->replaceDepartmentDeleteRule('transactions', 'department_id', 'restrict');
        $this->replaceDepartmentDeleteRule('users', 'department_id', 'restrict');
        $this->replaceDepartmentDeleteRule('documents', 'department_id', 'restrict');
    }

    public function down(): void
    {
        $this->replaceDepartmentDeleteRule('departments', 'parent_id', 'null');
        $this->replaceDepartmentDeleteRule('folders', 'department_id', 'null');
        $this->replaceDepartmentDeleteRule('transactions', 'department_id', 'cascade');
        $this->replaceDepartmentDeleteRule('users', 'department_id', 'null');
        $this->replaceDepartmentDeleteRule('documents', 'department_id', 'null');
    }

    private function replaceDepartmentDeleteRule(string $table, string $column, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column): void {
            $table->dropForeign([$column]);
        });

        Schema::table($table, function (Blueprint $table) use ($column, $onDelete): void {
            $foreign = $table->foreign($column)->references('id')->on('departments');

            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }
};
