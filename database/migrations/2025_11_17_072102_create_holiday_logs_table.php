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
        Schema::create('holiday_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('holidayid');       // primary key
            $table->string('employeeid');   // string employee id
            $table->decimal('numberofhours', 5, 2);
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_logs');
    }
};