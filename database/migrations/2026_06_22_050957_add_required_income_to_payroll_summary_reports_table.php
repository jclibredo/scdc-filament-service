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
            $table->decimal('required_income', 10, 2)
                ->default(0)
                ->after('required_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_summary_reports', function (Blueprint $table) {
            $table->dropColumn('required_income');
        });
    }
};
