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
      Schema::table('payroll_summary_reports', function (Blueprint $table) {
            // Drop the index first, then drop the column
            $table->dropIndex(['cat_id']); // Laravel names this convention: table_column_index
            $table->dropColumn('cat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('payroll_summary_reports', function (Blueprint $table) {
            // Recreate it if rolled back
            $table->integer('cat_id')->index()->nullable()->after('id');
        });
    }
};
