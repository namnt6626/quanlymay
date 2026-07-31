<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_periods', 'marketplace')) {
                $table->string('marketplace', 30)->default('tiktok')->after('id')->index();
            }
        });

        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_sku_summaries', 'marketplace')) {
                $table->string('marketplace', 30)->default('tiktok')->after('profit_analysis_period_id')->index();
            }
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_sku_maps', 'marketplace')) {
                $table->string('marketplace', 30)->default('tiktok')->after('id')->index();
            }
        });

        DB::table('profit_analysis_periods')->whereNull('marketplace')->orWhere('marketplace', '')->update(['marketplace' => 'tiktok']);
        DB::table('profit_analysis_sku_summaries')->whereNull('marketplace')->orWhere('marketplace', '')->update(['marketplace' => 'tiktok']);
        DB::table('profit_analysis_sku_maps')->whereNull('marketplace')->orWhere('marketplace', '')->update(['marketplace' => 'tiktok']);

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_periods_period_month_unique');
            $table->unique(['period_month', 'marketplace'], 'profit_analysis_period_month_marketplace_unique');
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_sku_maps_seller_sku_unique');
            $table->unique(['marketplace', 'seller_sku'], 'profit_analysis_sku_maps_marketplace_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_sku_maps_marketplace_sku_unique');
            $table->unique('seller_sku', 'profit_analysis_sku_maps_seller_sku_unique');
        });

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_period_month_marketplace_unique');
            $table->unique('period_month', 'profit_analysis_periods_period_month_unique');
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_sku_maps', 'marketplace')) {
                $table->dropColumn('marketplace');
            }
        });

        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_sku_summaries', 'marketplace')) {
                $table->dropColumn('marketplace');
            }
        });

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_periods', 'marketplace')) {
                $table->dropColumn('marketplace');
            }
        });
    }
};
