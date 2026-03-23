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
        Schema::create('employee_project_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employeeid');
            $table->unsignedBigInteger('projectid');
            $table->date('datestarted');
            $table->date('dateended')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();

            // Optional foreign keys if you have employees and projects tables
            // $table->foreign('employeeid')->references('id')->on('employees')->onDelete('cascade');
            // $table->foreign('projectid')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_project_histories');
    }
};
