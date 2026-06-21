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
        Schema::create('dm_mat_hang', function (Blueprint $table) {
            $table->id();
            $table->string('ma_hang', 50)->unique();
            $table->string('ten_hang');
            $table->text('mo_ta')->nullable();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dm_mat_hang');
    }
};
