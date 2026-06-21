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
        Schema::create('dm_don_vi_may', function (Blueprint $table) {
            $table->id();
            $table->string('ma_don_vi', 50)->index();
            $table->string('ten_don_vi');
            $table->foreignId('loai_don_vi_id')
                ->constrained('dm_loai_don_vi_may')
                ->restrictOnDelete();
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
        Schema::dropIfExists('dm_don_vi_may');
    }
};
