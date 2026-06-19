<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('userlogs', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->integer('created_by')->index(); // created_by column with index tracking
            $table->string('module');
            $table->string('module_id')->index(); // module_id column with index tracking
            $table->string('action');
            $table->text('details'); // used text datatype for logs detail payload to avoid 255 string limit truncation
            $table->timestamps(); // automatically provides created_at and updated_at
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('userlogs');
    }
};
