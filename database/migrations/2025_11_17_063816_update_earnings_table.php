<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earnings', function (Blueprint $table) {

            // Change employee_id from unsignedBigInteger to string employeeid
            $table->string('employeeid')->after('id');
            $table->dropColumn('employee_id');

            // category_id stays the same (unsignedBigInteger)

            // Update status from string to boolean
            $table->boolean('status')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('earnings', function (Blueprint $table) {

            // Revert employeeid back to employee_id (unsignedBigInteger)
            $table->unsignedBigInteger('employee_id')->after('id');
            $table->dropColumn('employeeid');

            // Revert status back to string
            $table->string('status')->default('active')->change();
        });
    }
};