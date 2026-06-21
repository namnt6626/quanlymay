<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'don_hang_chi_tiet_id')) {
            Schema::table('phieu_xuat_kho_chi_tiet', function (Blueprint $table) {
                $table->foreignId('don_hang_chi_tiet_id')
                    ->nullable()
                    ->after('nhap_kho_id')
                    ->constrained('don_hang_chi_tiets')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('phieu_xuat_kho_chi_tiet', 'don_hang_chi_tiet_id')) {
            Schema::table('phieu_xuat_kho_chi_tiet', function (Blueprint $table) {
                $table->dropConstrainedForeignId('don_hang_chi_tiet_id');
            });
        }
    }
};
