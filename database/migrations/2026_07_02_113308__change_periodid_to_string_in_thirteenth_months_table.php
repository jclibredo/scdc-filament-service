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
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // Changes the column type to string (VARCHAR 255 by default)
            $table->string('periodid')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // Reverts it back to an unsigned big integer if rolled back
            $table->unsignedBigInteger('periodid')->change();
        });
    }
};
