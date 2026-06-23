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
        Schema::table('emp_schedule', function (Blueprint $table) {
            $table->unsignedTinyInteger('workingHours')
                ->default(8)
                ->after('timeout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_schedule', function (Blueprint $table) {
            $table->dropColumn('workingHours');
        });
    }
};
