<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // 1. Drop unique constraint before changing column
            $table->dropUnique(['email']); // drops the existing UNIQUE index

            // 2. Make email nullable
            $table->string('email')->nullable()->change();

            // 3. Re-add unique index (optional but recommended)
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Reverse changes

            // Drop the modified unique index
            $table->dropUnique(['email']);

            // Change column back to NOT NULL
            $table->string('email')->nullable(false)->change();

            // Restore original unique index
            $table->unique('email');
        });
    }
};
