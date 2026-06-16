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
            // Adds the amount column (12 digits total, 2 after the decimal point)
            // Placed neatly after 'date_period_id' and defaults to 0.00
            $table->decimal('amount', 12, 2)->default(0.00)->after('date_period_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gov_deduction_logs', function (Blueprint $table) {
            // Drops the column if the migration is rolled back
            $table->dropColumn('amount');
        });
    }
};
