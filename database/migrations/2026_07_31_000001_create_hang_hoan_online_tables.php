<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hang_hoan_online')) {
            Schema::create('hang_hoan_online', function (Blueprint $table): void {
                $table->id();
                $table->date('ngay_hoan')->index();
                $table->date('tu_ngay')->nullable()->index();
                $table->date('den_ngay')->nullable()->index();
                $table->string('source', 30)->default('manual')->index();
                $table->string('ten_file', 255)->nullable();
                $table->unsignedInteger('tong_dong')->default(0);
                $table->decimal('tong_so_luong', 15, 4)->default(0);
                $table->decimal('tong_so_luong_cong_ton', 15, 4)->default(0);
                $table->text('ghi_chu')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('hang_hoan_online_chi_tiet')) {
            Schema::create('hang_hoan_online_chi_tiet', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('hang_hoan_online_id')->constrained('hang_hoan_online')->cascadeOnUpdate();
                $table->string('return_order_id', 100)->nullable()->index();
                $table->string('order_id', 100)->nullable()->index();
                $table->string('sku_id', 100)->nullable()->index();
                $table->string('seller_sku', 255)->nullable()->index();
                $table->string('ten_san_pham', 500)->nullable()->index();
                $table->string('mau', 255)->nullable()->index();
                $table->string('size', 100)->nullable()->index();
                $table->string('sku_name', 500)->nullable();
                $table->decimal('so_luong_hoan', 15, 4)->default(0);
                $table->string('return_type', 100)->nullable()->index();
                $table->string('return_status', 100)->nullable()->index();
                $table->string('tinh_trang_hang', 100)->default('ban_lai_duoc')->index();
                $table->boolean('cong_ton')->default(false)->index();
                $table->dateTime('time_requested')->nullable()->index();
                $table->dateTime('refund_time')->nullable()->index();
                $table->string('return_reason', 500)->nullable()->index();
                $table->string('tracking_id', 1000)->nullable();
                $table->string('compensation_status', 100)->nullable();
                $table->decimal('compensation_amount', 15, 2)->default(0);
                $table->text('buyer_note')->nullable();
                $table->string('dedupe_key', 500)->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique('dedupe_key', 'hang_hoan_online_dedupe_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hang_hoan_online_chi_tiet');
        Schema::dropIfExists('hang_hoan_online');
    }
};
