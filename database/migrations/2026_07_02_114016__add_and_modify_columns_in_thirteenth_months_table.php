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
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // 1. Add new columns
            $table->string('emptype');
            $table->string('empstatus');
            $table->string('partners')->nullable();
            $table->string('project');
            $table->decimal('allowance', 12, 2)->nullable(); // default precision used in Laravel
            $table->date('datestart')->nullable();
            $table->date('dateend')->nullable();

            // 2. Modify existing column to be nullable
            $table->string('periodid')->nullable()->change();

            // 3. Rename total_amount to earnings
            $table->renameColumn('total_amount', 'earnings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thirteenth_months', function (Blueprint $table) {
            // 1. Revert renamed column
            $table->renameColumn('earnings', 'total_amount');
            // 2. Revert periodid back to non-nullable (string)
            $table->string('periodid')->nullable(false)->change();
            // 3. Drop the newly added columns
            $table->dropColumn([
                'emptype',
                'empstatus',
                'partners',
                'project',
                'allowance',
                'datestart',
                'dateend'
            ]);
        });
    }
};
