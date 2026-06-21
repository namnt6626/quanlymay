<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhap_kho', function (Blueprint $table): void {
            if (! Schema::hasColumn('nhap_kho', 'loai_ton')) {
                $table->string('loai_ton', 20)->default('dat')->after('so_luong_nhap')->index();
            }

            if (! Schema::hasColumn('nhap_kho', 'auto_from_qc')) {
                $table->boolean('auto_from_qc')->default(false)->after('loai_ton')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nhap_kho', function (Blueprint $table): void {
            if (Schema::hasColumn('nhap_kho', 'auto_from_qc')) {
                $table->dropColumn('auto_from_qc');
            }

            if (Schema::hasColumn('nhap_kho', 'loai_ton')) {
                $table->dropColumn('loai_ton');
            }
        });
    }
};
