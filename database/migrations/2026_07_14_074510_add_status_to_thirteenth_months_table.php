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
            // Adds the 'status' boolean column, defaulting to true, placed right after yearendcode
            $table->boolean('status')->default(true)->after('yearendcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('thirteenth_months', function (Blueprint $table) {
            // Drop the status column if rolled back
            $table->dropColumn('status');
        });
    }
};
