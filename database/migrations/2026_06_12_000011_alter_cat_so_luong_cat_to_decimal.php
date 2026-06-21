<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::statement('ALTER TABLE cat MODIFY so_luong_cat DECIMAL(14,4) NOT NULL');
  }

  public function down(): void
  {
    DB::statement('ALTER TABLE cat MODIFY so_luong_cat INT UNSIGNED NOT NULL');
  }
};
