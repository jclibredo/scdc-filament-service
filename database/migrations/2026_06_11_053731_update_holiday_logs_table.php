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
        Schema::table('holiday_logs', function (Blueprint $table) {
            // 1. Add the new columns
            $table->dateTime('timein')->nullable()->after('employeeid');
            $table->dateTime('timeout')->nullable()->after('timein');
            $table->string('dateperiod_id')->index()->after('timeout');

            // 2. Drop the obsolete columns
            $table->dropColumn(['numberofhours', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holiday_logs', function (Blueprint $table) {
            // Re-add the dropped columns
            $table->decimal('numberofhours', 5, 2)->after('employeeid');
            $table->date('date')->after('numberofhours');

            // Drop the added columns & indexes
            $table->dropIndex(['dateperiod_id']);
            $table->dropColumn(['timein', 'timeout', 'dateperiod_id']);
        });
    }
};
