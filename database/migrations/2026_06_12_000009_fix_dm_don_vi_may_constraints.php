<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('dm_don_vi_may'));
        $uniqueIndex = $indexes->first(
            fn (array $index): bool => $index['unique']
                && $index['columns'] === ['ma_don_vi']
        );

        if ($uniqueIndex) {
            Schema::table('dm_don_vi_may', function (Blueprint $table) use ($uniqueIndex) {
                $table->dropUnique($uniqueIndex['name']);
            });
        }

        $hasRegularIndex = collect(Schema::getIndexes('dm_don_vi_may'))->contains(
            fn (array $index): bool => ! $index['unique']
                && $index['columns'] === ['ma_don_vi']
        );

        if (! $hasRegularIndex) {
            Schema::table('dm_don_vi_may', function (Blueprint $table) {
                $table->index('ma_don_vi');
            });
        }

        $hasForeignKey = collect(Schema::getForeignKeys('dm_don_vi_may'))->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['loai_don_vi_id']
        );

        if (! $hasForeignKey) {
            Schema::table('dm_don_vi_may', function (Blueprint $table) {
                $table->foreign('loai_don_vi_id')
                    ->references('id')
                    ->on('dm_loai_don_vi_may')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // This corrective migration intentionally keeps the SoftDeletes-safe schema.
    }
};
