<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropIndexIfExists('dm_size', 'dm_size_ma_size_unique');
        $this->dropIndexIfExists('dm_size', 'dm_size_ma_size_index');

        Schema::table('dm_size', function (Blueprint $table) {
            $table->index('ma_size');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('dm_size', 'dm_size_ma_size_index');

        Schema::table('dm_size', function (Blueprint $table) {
            $table->unique('ma_size');
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
