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
        Schema::create('payroll_summary_reports', function (Blueprint $table) {
            // id(primary, index)
            $table->id();

            // string fields with indexes
            $table->string('dateperiod_id')->index();
            $table->string('employee_id')->index();
            $table->decimal('totalhours', 8, 2)->default(0);
            $table->decimal('totalovertime', 8, 2)->default(0);
            $table->decimal('totalabsent', 8, 2)->default(0);
            $table->decimal('lateundertime', 8, 2)->default(0);
            $table->decimal('totaldeductionn', 8, 2)->default(0); // Kept your exact spelling here!
            $table->decimal('totalearnings', 12, 2)->default(0);
            $table->decimal('totaladjustment', 12, 2)->default(0);
            $table->decimal('totalnetpay', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_summary_reports');
    }
};
