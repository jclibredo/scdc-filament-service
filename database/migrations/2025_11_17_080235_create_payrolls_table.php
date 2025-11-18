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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id(); // primary key
            $table->string('employeeid'); // string employee id
            $table->unsignedBigInteger('period'); // e.g., "2025-11"
            $table->decimal('totalhours', 8, 2);
            $table->decimal('acquirehours', 8, 2);
            $table->boolean('status')->default(true); // active by default
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};