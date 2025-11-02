<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ 1. Drop unique index first
            // Laravel auto-generates index name as "employees_email_unique"
            $table->dropUnique('employees_email_unique');

            // ✅ 2. Make email nullable
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // ✅ Reverse: make NOT NULL again
            $table->string('email')->nullable(false)->change();

            // ✅ Recreate unique index
            $table->unique('email', 'employees_email_unique');
        });
    }
};
