<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ Step 1: Drop existing FK
            $table->dropForeign(['project_id']);

            // ✅ Step 2: Change project_id to string to match project_code
            $table->string('project_id')->nullable()->change();

            // ✅ Step 3: Add new foreign key referencing project_code
            $table->foreign('project_id')
                ->references('project_code')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // ✅ Rollback properly

            // Drop new FK
            $table->dropForeign(['project_id']);

            // Convert project_id back to unsignedBigInteger
            $table->unsignedBigInteger('project_id')->nullable()->change();

            // Restore old FK referencing id
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });
    }
};
