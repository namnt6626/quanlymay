<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_loai_don_vi_may', function (Blueprint $table) {
            $table->id();
            $table->string('ma_loai', 50)->index();
            $table->string('ten_loai');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_loai_don_vi_may');
    }
};
