<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {
            // 1. Drop foreign key first
            $table->dropForeign(['employeeid']);

            // 2. Change column type from unsignedBigInteger to string
            $table->string('employeeid')->change();

            // 3. Add the new foreign key referencing employees.employeeid
            $table->foreign('employeeid')
                ->references('employeeid')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {
            // Reverse changes

            // Drop new FK
            $table->dropForeign(['employeeid']);

            // Change back to original type
            $table->unsignedBigInteger('employeeid')->change();

            // Restore original FK referencing employees.id
            $table->foreign('employeeid')
                ->references('id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }
};
