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
        Schema::table('dm_don_vi_cat', function (Blueprint $table) {
            $table->dropUnique('dm_don_vi_cat_ma_don_vi_unique');
            $table->index('ma_don_vi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dm_don_vi_cat', function (Blueprint $table) {
            $table->dropIndex('dm_don_vi_cat_ma_don_vi_index');
            $table->unique('ma_don_vi');
        });
    }
};
