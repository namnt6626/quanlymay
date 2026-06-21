<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    $driver = Schema::getConnection()->getDriverName();

    if ($driver === 'mysql') {
      DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
      return;
    }

    Schema::table('users', function (Blueprint $table) {
      $table->string('email')->nullable()->change();
    });
  }

  public function down(): void
  {
    $driver = Schema::getConnection()->getDriverName();

    if ($driver === 'mysql') {
      DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
      return;
    }

    Schema::table('users', function (Blueprint $table) {
      $table->string('email')->nullable(false)->change();
    });
  }
};
