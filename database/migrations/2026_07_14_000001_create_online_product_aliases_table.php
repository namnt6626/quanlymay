<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('online_product_aliases')) {
            return;
        }

        Schema::create('online_product_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name', 500)->unique();
            $table->string('group_name', 500)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_product_aliases');
    }
};
