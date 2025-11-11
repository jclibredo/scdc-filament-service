<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->after('date_period_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deduction_logs', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};