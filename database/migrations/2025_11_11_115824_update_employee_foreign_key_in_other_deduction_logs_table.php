<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // Drop the old foreign key
            // $table->dropForeign(['employee_id']);

            // Add the new foreign key referencing employees.employeeid
            $table->foreign('employee_id')
                ->references('employeeid')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // Drop the fixed foreign key
            // $table->dropForeign(['employee_id']);

            // Restore old foreign key referencing id (if needed)
            $table->foreign('employee_id')
                ->references('employeeid')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }
};
