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
        Schema::table('year_end_reports', function (Blueprint $table) {
            // Adds the nullable string column right after 'dateto'
            $table->string('rep_type')->nullable()->after('dateto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('year_end_reports', function (Blueprint $table) {
            // Drops the column if the migration is rolled back
            $table->dropColumn('rep_type');
        });
    }
};
