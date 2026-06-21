<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasColumn('cat', 'don_hang_chi_tiet_id')) {
      return;
    }

    Schema::table('cat', function (Blueprint $table) {
      $table->foreignId('don_hang_chi_tiet_id')
        ->nullable()
        ->after('size_id')
        ->constrained('don_hang_chi_tiets')
        ->nullOnDelete();
    });
  }

  public function down(): void
  {
    if (! Schema::hasColumn('cat', 'don_hang_chi_tiet_id')) {
      return;
    }

    Schema::table('cat', function (Blueprint $table) {
      $table->dropConstrainedForeignId('don_hang_chi_tiet_id');
    });
  }
};
