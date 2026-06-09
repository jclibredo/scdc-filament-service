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
        Schema::table('categories', function (Blueprint $table) {
            // Adding the requested columns
            $table->string('cat')->nullable()->after('name');
            $table->boolean('status')->default(true)->after('cat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Dropping the columns if the migration is rolled back
            $table->dropColumn(['cat', 'status']);
        });
    }
};
