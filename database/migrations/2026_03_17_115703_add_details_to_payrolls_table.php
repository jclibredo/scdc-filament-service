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
        Schema::table('payrolls', function (Blueprint $table) {
            // Days of the week
            $table->decimal('Sunday', 10, 2)->nullable()->after('acquirehours');
            $table->decimal('Monday', 10, 2)->nullable()->after('Sunday');
            $table->decimal('Tuesday', 10, 2)->nullable()->after('Monday');
            $table->decimal('Wednesday', 10, 2)->nullable()->after('Tuesday');
            $table->decimal('Thursday', 10, 2)->nullable()->after('Wednesday');
            $table->decimal('Friday', 10, 2)->nullable()->after('Thursday');
            $table->decimal('Saturday', 10, 2)->nullable()->after('Friday');
            // Additional Details
            $table->decimal('RegularOT', 10, 2)->nullable()->after('Saturday');
            // Foreign Keys / IDs
            $table->unsignedBigInteger('Project')->nullable()->after('RegularOT');
            $table->unsignedBigInteger('created_by')->nullable()->after('Project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'RegularOT',
                'Project',
                'created_by'
            ]);
        });
    }
};
