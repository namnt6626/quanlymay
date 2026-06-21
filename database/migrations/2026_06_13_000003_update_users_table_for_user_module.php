<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('username', 100)->nullable()->unique()->after('name');
      $table->string('phone', 20)->nullable()->after('email');
      $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
      $table->boolean('status')->default(true)->after('role_id');
      $table->timestamp('last_login_at')->nullable()->after('status');
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropSoftDeletes();
      $table->dropConstrainedForeignId('role_id');
      $table->dropColumn(['username', 'phone', 'status', 'last_login_at']);
    });
  }
};
