<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dm_kenh_ban', function (Blueprint $table) {
            $table->id();
            $table->string('ma_kenh', 50)->index();
            $table->string('ten_kenh', 150)->index();
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $existingNames = collect(['don_hangs', 'don_hang_hoan_thanh', 'phieu_xuat_kho'])
            ->filter(fn (string $table): bool => Schema::hasTable($table) && Schema::hasColumn($table, 'kenh_ban'))
            ->flatMap(fn (string $table) => DB::table($table)
                ->whereNotNull('kenh_ban')
                ->where('kenh_ban', '<>', '')
                ->distinct()
                ->pluck('kenh_ban'))
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->all();

        $channelNames = collect(['Tiktok', 'Shopee', 'Bán sỉ', ...$existingNames])
            ->unique()
            ->values();

        foreach ($channelNames as $index => $tenKenh) {
            $maKenh = trim((string) preg_replace('/[^A-Z0-9]+/', '_', strtoupper(Str::ascii($tenKenh))), '_');

            DB::table('dm_kenh_ban')->insert([
                'ma_kenh' => $maKenh !== '' ? $maKenh : 'KENH_'.($index + 1),
                'ten_kenh' => $tenKenh,
                'trang_thai' => true,
                'created_at' => now()->addSeconds($index),
                'updated_at' => now()->addSeconds($index),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dm_kenh_ban');
    }
};
