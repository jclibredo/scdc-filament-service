<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atlogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->time('timein')->nullable();
            $table->time('timeout')->nullable();
            $table->dateTime('datetime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atlogs');
    }
};
