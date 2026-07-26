<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profit_analysis_periods')) {
            Schema::create('profit_analysis_periods', function (Blueprint $table): void {
                $table->id();
                $table->date('period_month')->unique();
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->string('label', 50);
                $table->unsignedInteger('sku_count')->default(0);
                $table->unsignedInteger('missing_cost_count')->default(0);
                $table->unsignedInteger('order_count')->default(0);
                $table->unsignedInteger('item_count')->default(0);
                $table->decimal('gmv', 18, 2)->default(0);
                $table->decimal('settlement_revenue', 18, 2)->default(0);
                $table->decimal('marketplace_fees', 18, 2)->default(0);
                $table->decimal('ad_cost', 18, 2)->default(0);
                $table->decimal('cogs', 18, 2)->default(0);
                $table->decimal('total_revenue', 18, 2)->default(0);
                $table->decimal('total_cost', 18, 2)->default(0);
                $table->decimal('profit', 18, 2)->default(0);
                $table->decimal('profit_per_order', 18, 2)->default(0);
                $table->decimal('ad_breakeven', 18, 2)->default(0);
                $table->json('source_totals')->nullable();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('profit_analysis_sku_maps')) {
            Schema::create('profit_analysis_sku_maps', function (Blueprint $table): void {
                $table->id();
                $table->string('seller_sku', 255)->unique();
                $table->string('fob_sku', 255)->nullable()->index();
                $table->string('fob_code', 255)->nullable();
                $table->string('product_name', 1000)->nullable();
                $table->decimal('unit_cost', 18, 2)->nullable();
                $table->enum('source', ['auto', 'manual'])->default('manual');
                $table->enum('status', ['mapped', 'missing_cost'])->default('missing_cost');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('profit_analysis_sku_summaries')) {
            Schema::create('profit_analysis_sku_summaries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('profit_analysis_period_id')->constrained('profit_analysis_periods')->cascadeOnDelete();
                $table->string('seller_sku', 255);
                $table->string('fob_sku', 255)->nullable();
                $table->string('product_name', 1000)->nullable();
                $table->decimal('unit_cost', 18, 2)->default(0);
                $table->decimal('quantity_sold', 18, 4)->default(0);
                $table->decimal('quantity_returned', 18, 4)->default(0);
                $table->decimal('net_quantity', 18, 4)->default(0);
                $table->decimal('revenue', 18, 2)->default(0);
                $table->decimal('refund_amount', 18, 2)->default(0);
                $table->decimal('cogs', 18, 2)->default(0);
                $table->decimal('allocated_fees', 18, 2)->default(0);
                $table->decimal('allocated_ad_cost', 18, 2)->default(0);
                $table->decimal('profit', 18, 2)->default(0);
                $table->decimal('profit_per_unit', 18, 2)->default(0);
                $table->enum('status', ['profit', 'loss', 'missing_cost'])->default('missing_cost');
                $table->timestamps();
                $table->unique(['profit_analysis_period_id', 'seller_sku'], 'profit_analysis_period_sku_unique');
                $table->index(['status', 'profit']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_analysis_sku_summaries');
        Schema::dropIfExists('profit_analysis_sku_maps');
        Schema::dropIfExists('profit_analysis_periods');
    }
};
