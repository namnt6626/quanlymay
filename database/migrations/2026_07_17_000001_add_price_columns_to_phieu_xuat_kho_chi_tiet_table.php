<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_xuat_kho_chi_tiet', function (Blueprint $table): void {
            if (! Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'don_gia')) {
                $table->decimal('don_gia', 15, 2)->default(0)->after('so_luong_xuat');
            }

            if (! Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'thanh_tien')) {
                $table->decimal('thanh_tien', 15, 2)->default(0)->after('don_gia');
            }
        });
    }

    public function down(): void
    {
        Schema::table('phieu_xuat_kho_chi_tiet', function (Blueprint $table): void {
            if (Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'thanh_tien')) {
                $table->dropColumn('thanh_tien');
            }

            if (Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'don_gia')) {
                $table->dropColumn('don_gia');
            }
        });
    }
};
