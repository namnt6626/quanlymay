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
        Schema::table('dm_mat_hang', function (Blueprint $table) {
            $table->dropUnique('dm_mat_hang_ma_hang_unique');
            $table->index('ma_hang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dm_mat_hang', function (Blueprint $table) {
            $table->dropIndex('dm_mat_hang_ma_hang_index');
            $table->unique('ma_hang');
        });
    }
};
