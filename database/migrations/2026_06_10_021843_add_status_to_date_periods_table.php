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
        Schema::table('date_periods', function (Blueprint $table) {
            // 💡 Adds the status column as boolean, defaults to true, placed after dateto
            $table->boolean('status')->default(true)->after('dateto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('date_periods', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
