<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hang_hoan_online_chi_tiet')) {
            Schema::table('hang_hoan_online_chi_tiet', function (Blueprint $table): void {
                $table->string('tracking_id', 1000)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hang_hoan_online_chi_tiet')) {
            Schema::table('hang_hoan_online_chi_tiet', function (Blueprint $table): void {
                $table->string('tracking_id', 100)->nullable()->change();
            });
        }
    }
};
