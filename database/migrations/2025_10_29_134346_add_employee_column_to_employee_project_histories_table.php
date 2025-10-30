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
        Schema::table('employee_project_histories', function (Blueprint $table) {
            $table->string('employee')->nullable()->after('employeeid');
        });
    }

    public function down(): void
    {
        Schema::table('employee_project_histories', function (Blueprint $table) {
            $table->dropColumn('employee');
        });
    }
};
