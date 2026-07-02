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
        Schema::table('year_end_reports', function (Blueprint $table) {
            // Adds the date columns
            $table->date('datefrom')->nullable()->after('status');
            $table->date('dateto')->nullable()->after('datefrom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('year_end_reports', function (Blueprint $table) {
            // Drops the columns if rolled back
            $table->dropColumn(['datefrom', 'dateto']);
        });
    }
};
