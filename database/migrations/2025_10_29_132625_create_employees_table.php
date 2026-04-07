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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employeeid', 4)->unique(); // 4-digit auto-generated code
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->boolean('status')->default(true);
            $table->string('mobile')->nullable();
            $table->string('email')->unique();
            $table->date('birthdate')->nullable();
            $table->enum('sex', ['Male', 'Female', 'Other'])->nullable();
            $table->text('address')->nullable();
            $table->date('datehired')->nullable();
            $table->date('dateseperated')->nullable();
            $table->unsignedBigInteger('skill_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
