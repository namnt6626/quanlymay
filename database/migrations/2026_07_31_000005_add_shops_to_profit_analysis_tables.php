<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profit_analysis_shops')) {
            Schema::create('profit_analysis_shops', function (Blueprint $table): void {
                $table->id();
                $table->string('marketplace', 30)->index();
                $table->string('name', 255);
                $table->string('normalized_name', 255);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['marketplace', 'normalized_name'], 'profit_analysis_shops_marketplace_name_unique');
            });
        }

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_periods', 'shop_id')) {
                $table->foreignId('shop_id')->nullable()->after('marketplace')->constrained('profit_analysis_shops')->nullOnDelete();
            }
        });

        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_sku_summaries', 'shop_id')) {
                $table->foreignId('shop_id')->nullable()->after('marketplace')->constrained('profit_analysis_shops')->nullOnDelete();
            }
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_sku_maps', 'shop_id')) {
                $table->foreignId('shop_id')->nullable()->after('marketplace')->constrained('profit_analysis_shops')->nullOnDelete();
            }
        });

        $this->backfillDefaultShops();

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_period_month_marketplace_unique');
            $table->unique(['period_month', 'marketplace', 'shop_id'], 'profit_analysis_period_month_marketplace_shop_unique');
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_sku_maps_marketplace_sku_unique');
            $table->unique(['marketplace', 'shop_id', 'seller_sku'], 'profit_analysis_sku_maps_marketplace_shop_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_sku_maps_marketplace_shop_sku_unique');
            $table->unique(['marketplace', 'seller_sku'], 'profit_analysis_sku_maps_marketplace_sku_unique');
        });

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            $table->dropUnique('profit_analysis_period_month_marketplace_shop_unique');
            $table->unique(['period_month', 'marketplace'], 'profit_analysis_period_month_marketplace_unique');
        });

        Schema::table('profit_analysis_sku_maps', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_sku_maps', 'shop_id')) {
                $table->dropConstrainedForeignId('shop_id');
            }
        });

        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_sku_summaries', 'shop_id')) {
                $table->dropConstrainedForeignId('shop_id');
            }
        });

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            if (Schema::hasColumn('profit_analysis_periods', 'shop_id')) {
                $table->dropConstrainedForeignId('shop_id');
            }
        });

        Schema::dropIfExists('profit_analysis_shops');
    }

    private function backfillDefaultShops(): void
    {
        $marketplaces = collect(['tiktok', 'shopee'])
            ->merge(DB::table('profit_analysis_periods')->distinct()->pluck('marketplace'))
            ->merge(DB::table('profit_analysis_sku_maps')->distinct()->pluck('marketplace'))
            ->filter()
            ->unique()
            ->values();

        foreach ($marketplaces as $marketplace) {
            $shopId = DB::table('profit_analysis_shops')
                ->where('marketplace', $marketplace)
                ->where('normalized_name', 'shop mac dinh')
                ->value('id');

            if (! $shopId) {
                $shopId = DB::table('profit_analysis_shops')->insertGetId([
                    'marketplace' => $marketplace,
                    'name' => 'Shop mặc định',
                    'normalized_name' => 'shop mac dinh',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('profit_analysis_periods')
                ->where('marketplace', $marketplace)
                ->whereNull('shop_id')
                ->update(['shop_id' => $shopId]);

            DB::table('profit_analysis_sku_summaries')
                ->where('marketplace', $marketplace)
                ->whereNull('shop_id')
                ->update(['shop_id' => $shopId]);

            DB::table('profit_analysis_sku_maps')
                ->where('marketplace', $marketplace)
                ->whereNull('shop_id')
                ->update(['shop_id' => $shopId]);
        }
    }
};
