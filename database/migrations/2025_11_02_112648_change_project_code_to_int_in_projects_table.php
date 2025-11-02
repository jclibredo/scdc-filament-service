<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // ✅ Drop unique index first (Laravel auto-names it)
            $table->dropUnique('projects_project_code_unique');

            // ✅ If other tables reference project_code, drop those FKs BEFORE changing type
            // Example: employee_project_histories.projectid
            // $table->dropForeign(['projectid']);  <-- uncomment if FK exists

            // ✅ Change project_code from string → unsignedBigInteger
            $table->unsignedBigInteger('project_code')->change();

            // ✅ Re-create unique index if still needed
            $table->unique('project_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // ✅ Drop unique index for rollback
            $table->dropUnique('projects_project_code_unique');

            // ✅ Change project_code back to string
            $table->string('project_code')->change();

            // ✅ Re-add unique index
            $table->unique('project_code');

            // ✅ Re-add your foreign key if needed
            // $table->foreign('projectid')->references('project_code')->on('projects');
        });
    }
};
