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
        Schema::table('date_periods', function (Blueprint $blueprint) {
            // total digits = 8, decimal places = 2 (allows values like 120.00, 1.25, etc.)
            $blueprint->decimal('overtime_rate', 8, 2)
                ->nullable()
                ->after('status') // Places it cleanly after your status column
                ->comment('Overtime multiplier percentage or baseline rate setting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('date_periods', function (Blueprint $blueprint) {
            $blueprint->dropColumn('overtime_rate');
        });
    }
};
