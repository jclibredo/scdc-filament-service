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
        Schema::create('other_deduction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('other_deduction_id')->index();
            $table->string('employee_id')->index();
            $table->unsignedBigInteger('date_period_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_deduction_logs');
    }
};
