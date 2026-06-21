<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (! Schema::hasColumn('qc', 'don_hang_chi_tiet_id')) {
      Schema::table('qc', function (Blueprint $table) {
        $table->foreignId('don_hang_chi_tiet_id')
          ->nullable()
          ->after('phan_bo_may_id')
          ->constrained('don_hang_chi_tiets')
          ->nullOnDelete();
      });

      return;
    }

    $hasForeignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
      ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
      ->where('TABLE_NAME', 'qc')
      ->where('COLUMN_NAME', 'don_hang_chi_tiet_id')
      ->whereNotNull('REFERENCED_TABLE_NAME')
      ->exists();

    if (! $hasForeignKey) {
      Schema::table('qc', function (Blueprint $table) {
        $table->foreign('don_hang_chi_tiet_id')
          ->references('id')
          ->on('don_hang_chi_tiets')
          ->nullOnDelete();
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('qc', 'don_hang_chi_tiet_id')) {
      Schema::table('qc', function (Blueprint $table) {
        $table->dropConstrainedForeignId('don_hang_chi_tiet_id');
      });
    }
  }
};
