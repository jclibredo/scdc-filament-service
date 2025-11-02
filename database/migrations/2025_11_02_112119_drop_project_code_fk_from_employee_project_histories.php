<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {

            // ✅ Step 1: Drop the foreign key referencing project_code
            // Laravel usually names this as:
            // employee_project_histories_projectid_foreign
            // But confirm with SHOW CREATE TABLE if needed.
            $table->dropForeign(['projectid']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {
            // ✅ Recreate FK if rollback is needed
            $table->foreign('projectid')
                ->references('project_code')
                ->on('projects')
                ->cascadeOnDelete();

            // ✅ Recreate index if needed
            $table->index('projectid', 'employee_project_histories_projectid_foreign');
        });
    }
};
