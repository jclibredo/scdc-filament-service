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
        Schema::create('year_end_reports', function (Blueprint $table) {
            $table->id(); // Primary key (auto-increments and indexes by default)
            $table->string('code')->index();
            $table->string('emptype')->index();
            $table->string('empstatus')->index();
            $table->string('partners')->nullable()->index();
            $table->string('projectid')->nullable()->index();
            $table->boolean('status')->default(true)->index(); // default(1) maps to true
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('year_end_reports');
    }
};
