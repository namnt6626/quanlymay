<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('don_hang_hoan_thanh', 'kenh_ban')) {
            return;
        }

        Schema::table('don_hang_hoan_thanh', function (Blueprint $table): void {
            $table->string('kenh_ban', 100)->default('Online')->after('ten_kho')->index();
        });
    }

    public function down(): void
    {
        Schema::table('don_hang_hoan_thanh', fn (Blueprint $table) => $table->dropColumn('kenh_ban'));
    }
};
