<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {

            // ✅ Drop INDEX (because there is no foreign key)
            $table->dropIndex('employee_project_histories_projectid_foreign');

            // ✅ Change type to string (since project_code is string)
            $table->string('projectid')->change();

            // ✅ Add new foreign key constraint
            $table->foreign('projectid')
                ->references('project_code')
                ->on('projects')
                ->cascadeOnDelete();  // or nullOnDelete()
        });
    }

    public function down(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {

            // ✅ Drop the new FK
            $table->dropForeign(['projectid']);

            // ✅ Restore previous type
            $table->string('projectid')->change();

            // ✅ Restore original index
            $table->index('projectid', 'employee_project_histories_projectid_foreign');
        });
    }
};
