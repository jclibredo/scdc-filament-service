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
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id()->index(); // Primary ID with explicit index
            $table->string('employee_id')->index();
            $table->string('date_period_id')->index();
            $table->string('adjustment_id')->index();
            $table->decimal('amount', 12, 2)->default(0.00); // 12 total digits, 2 decimal places
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
