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
            // 1. Add column with an index
            $table->string('yearendrepid')->index();

            // 2. Drop the columns
            $table->dropColumn(['emptype', 'empstatus']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // 1. Re-add the columns if rolled back
            $table->string('emptype')->nullable(); // Good practice to match their previous state
            $table->string('empstatus')->nullable();

            // 2. Drop the index explicitly using Laravel's named convention string, then drop column
            $table->dropIndex('thirteenth_months_yearendrepid_index');
            $table->dropColumn('yearendrepid');
        });
    }
};
