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
      Schema::create('incentive_bonuses', function (Blueprint $table) {
            $table->id();
            $table->string('employeeid')->index();
            $table->string('yearendrepid')->index();
            $table->boolean('status')->default(true);
            $table->decimal('earnings', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentive_bonuses');
    }
};
