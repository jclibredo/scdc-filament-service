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
            // Adds 'projectid' as a nullable string and applies an index
            $table->string('projectid')
                ->nullable()
                ->index()
                ->after('category_id'); // Placed nicely after category_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('date_periods', function (Blueprint $table) {
            // Drops the column and automatically removes its index
            $table->dropColumn('projectid');
        });
    }
};
