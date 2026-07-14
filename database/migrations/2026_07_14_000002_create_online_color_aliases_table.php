<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_color_aliases', function (Blueprint $table): void {
            $table->id();
            $table->string('original_name', 255)->unique();
            $table->string('group_name', 255)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_color_aliases');
    }
};
