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
       Schema::table('employees', function (Blueprint $table) {
            // We change the type to string and keep it nullable as per your original schema
            $table->string('project_id')->nullable()->change(); // Adding index for better performance on lookups
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('employees', function (Blueprint $table) {
            // Revert back to the original unsignedBigInteger
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });
    }
};
