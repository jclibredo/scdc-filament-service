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
        Schema::table('gov_deduction_logs', function (Blueprint $table) {
            // 1. Drop the existing index first to avoid the duplicate name error
            $table->dropIndex('gov_deduction_logs_date_period_id_index');
        });

        Schema::table('gov_deduction_logs', function (Blueprint $table) {
            // 2. Safely change the type and rebuild the index
            $table->string('date_period_id')->index()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gov_deduction_logs', function (Blueprint $table) {
            $table->dropIndex('gov_deduction_logs_date_period_id_index');
        });

        Schema::table('gov_deduction_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('date_period_id')->index()->change();
        });
    }
};
