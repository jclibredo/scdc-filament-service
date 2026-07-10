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
       Schema::create('activity_logs', function (Blueprint $table) {
            $table->id(); // id (primary)
            $table->string('user_id')->index(); // user_id (string, index)
            $table->string('activity'); // activity (string)
            $table->string('module'); // module (string)
            $table->string('ipaddress'); // ipaddress (string)
            $table->string('windows'); // windows (string)
            $table->timestamps(); // Optional: Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
