<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'qc')
            ->where('COLUMN_NAME', 'phan_bo_may_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreignKey) {
            Schema::table('qc', function (Blueprint $table) use ($foreignKey): void {
                $table->dropForeign($foreignKey);
            });
        }

        DB::statement('ALTER TABLE qc MODIFY phan_bo_may_id BIGINT UNSIGNED NULL');

        if ($foreignKey) {
            Schema::table('qc', function (Blueprint $table): void {
                $table->foreign('phan_bo_may_id')
                    ->references('id')
                    ->on('phan_bo_may')
                    ->nullOnDelete();
            });
        }

        Schema::table('qc', function (Blueprint $table): void {
            if (! Schema::hasColumn('qc', 'mat_hang_id')) {
                $table->foreignId('mat_hang_id')
                    ->nullable()
                    ->after('don_hang_chi_tiet_id')
                    ->constrained('dm_mat_hang')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('qc', 'mau_id')) {
                $table->foreignId('mau_id')
                    ->nullable()
                    ->after('mat_hang_id')
                    ->constrained('dm_mau')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('qc', 'size_id')) {
                $table->foreignId('size_id')
                    ->nullable()
                    ->after('mau_id')
                    ->constrained('dm_size')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('qc', function (Blueprint $table): void {
            if (Schema::hasColumn('qc', 'size_id')) {
                $table->dropConstrainedForeignId('size_id');
            }

            if (Schema::hasColumn('qc', 'mau_id')) {
                $table->dropConstrainedForeignId('mau_id');
            }

            if (Schema::hasColumn('qc', 'mat_hang_id')) {
                $table->dropConstrainedForeignId('mat_hang_id');
            }
        });
    }
};
