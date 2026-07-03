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
            // Converts the existing project column to be nullable
            $table->string('project')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // Reverts the project column back to required (NOT NULL)
            // Note: Ensure your database rows don't contain NULL records before rolling back!
            $table->string('project')->nullable(false)->change();
        });
    }
};
