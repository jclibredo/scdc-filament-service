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
        Schema::create('payroll_reports', function (Blueprint $table) {
            $table->id();
            $table->string('dateperiod_id')->index();
            $table->string('employee_id')->index();
            $table->string('paytype')->index();
            $table->date('date_entry');
            $table->decimal('overtime', 8, 2)->default(0);
            $table->decimal('acquired_hours', 8, 2)->default(0);
            $table->decimal('late_undertime', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_reports');
    }
};
