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
            // 1. Drop the existing automatic index first to prevent conflicts
            $table->dropIndex('other_deduction_logs_date_period_id_index');
        });

        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // 2. Change type to string and rebuild the index safely
            $table->string('date_period_id')->index()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // 1. Drop the string index first during rollback
            $table->dropIndex('other_deduction_logs_date_period_id_index');
        });

        Schema::table('other_deduction_logs', function (Blueprint $table) {
            // 2. Revert back to the original unsigned big integer and index it
            $table->unsignedBigInteger('date_period_id')->index()->change();
        });
    }
};
