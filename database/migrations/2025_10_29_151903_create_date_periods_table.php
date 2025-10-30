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
        Schema::create('date_periods', function (Blueprint $table) {
            $table->id();
            $table->string('employeetype'); // Semi-monthly, Weekly
            $table->unsignedBigInteger('category_id');
            $table->date('datefrom');
            $table->date('dateto');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('date_periods');
    }
};
