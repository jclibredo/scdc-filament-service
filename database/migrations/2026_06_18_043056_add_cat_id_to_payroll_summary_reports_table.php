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
        Schema::table('payroll_summary_reports', function (Blueprint $table) {
            // Adds cat_id as an integer and indexes it
            $table->integer('cat_id')->index()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_summary_reports', function (Blueprint $table) {
            $table->dropColumn('cat_id'); // This automatically drops the single column index too
        });
    }
};
