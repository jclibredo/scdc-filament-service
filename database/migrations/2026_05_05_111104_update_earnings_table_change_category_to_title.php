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
        Schema::table('earnings', function (Blueprint $table) {
            // Drop the old category_id column
            $table->dropColumn('category_id');

            // Add the new title column as a nullable string
            $table->string('title')->nullable()->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {
            // Rollback: Remove title and bring back category_id
            $table->dropColumn('title');
            $table->unsignedBigInteger('category_id')->index()->after('employee_id');
        });
    }
};
