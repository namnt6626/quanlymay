<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat', function (Blueprint $table) {
            $table->id();
            $table->date('ngay_cat')->index();
            $table->foreignId('mat_hang_id')
                ->constrained('dm_mat_hang')
                ->restrictOnDelete();
            $table->foreignId('mau_id')
                ->constrained('dm_mau')
                ->restrictOnDelete();
            $table->foreignId('size_id')
                ->constrained('dm_size')
                ->restrictOnDelete();
            $table->foreignId('ban_cat_id')
                ->constrained('dm_ban_cat')
                ->restrictOnDelete();
            $table->foreignId('don_vi_cat_id')
                ->constrained('dm_don_vi_cat')
                ->restrictOnDelete();
            $table->unsignedInteger('so_luong_cat');
            $table->decimal('dinh_muc', 12, 4);
            $table->decimal('vai_tieu_hao', 14, 4);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat');
    }
};
