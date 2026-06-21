<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dm_don_vi_may', 'loai_don_vi_id')) {
            Schema::table('dm_don_vi_may', function (Blueprint $table) {
                $table->dropConstrainedForeignId('loai_don_vi_id');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid restoring an unused field.
    }
};