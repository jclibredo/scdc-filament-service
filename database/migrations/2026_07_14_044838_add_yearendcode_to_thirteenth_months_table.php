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
            // Adds the 'yearendcode' string column with an index right after employeeid
            $table->string('yearendcode')->nullable()->index()->after('employeeid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // 1. Drop the index first (Laravel naming convention: table_column_index)
            $table->dropIndex(['yearendcode']);

            // 2. Drop the column
            $table->dropColumn('yearendcode');
        });
    }
};
