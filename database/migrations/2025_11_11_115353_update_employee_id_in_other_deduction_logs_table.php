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

        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // Drop the foreign key by its name
            // $table->dropForeign(['employee_id']);

            // Change employee_id column type to string
            $table->string('employee_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // Change employee_id back to unsignedBigInteger
            $table->unsignedBigInteger('employee_id')->change();

            // Restore the foreign key if needed
            $table->foreign('employee_id')
                ->references('employeeid')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }
};
