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
            // 2. Add the new grosspay column
            $table->decimal('grosspay', 12, 2)->default(0.00)->after('totalnetpay');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_summary_reports', function (Blueprint $table) {
            // 1. Remove the added grosspay column
            $table->dropColumn('grosspay');
        });
    }
};
