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
            if (! Schema::hasColumn('profit_analysis_periods', 'sku_revenue_total')) {
                $table->decimal('sku_revenue_total', 18, 2)->default(0)->after('settlement_revenue');
            }
            if (! Schema::hasColumn('profit_analysis_periods', 'revenue_adjustment')) {
                $table->decimal('revenue_adjustment', 18, 2)->default(0)->after('sku_revenue_total');
            }
            if (! Schema::hasColumn('profit_analysis_periods', 'completed_order_count')) {
                $table->unsignedInteger('completed_order_count')->default(0)->after('order_count');
            }
            if (! Schema::hasColumn('profit_analysis_periods', 'analytics_order_count')) {
                $table->unsignedInteger('analytics_order_count')->default(0)->after('completed_order_count');
            }
        });

        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            if (! Schema::hasColumn('profit_analysis_sku_summaries', 'original_revenue')) {
                $table->decimal('original_revenue', 18, 2)->default(0)->after('net_quantity');
            }
            if (! Schema::hasColumn('profit_analysis_sku_summaries', 'allocated_revenue_adjustment')) {
                $table->decimal('allocated_revenue_adjustment', 18, 2)->default(0)->after('refund_amount');
            }
            if (! Schema::hasColumn('profit_analysis_sku_summaries', 'final_revenue')) {
                $table->decimal('final_revenue', 18, 2)->default(0)->after('allocated_revenue_adjustment');
            }
        });

        $this->backfillExistingPeriods();
    }

    public function down(): void
    {
        Schema::table('profit_analysis_sku_summaries', function (Blueprint $table): void {
            foreach (['final_revenue', 'allocated_revenue_adjustment', 'original_revenue'] as $column) {
                if (Schema::hasColumn('profit_analysis_sku_summaries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            foreach (['analytics_order_count', 'completed_order_count', 'revenue_adjustment', 'sku_revenue_total'] as $column) {
                if (Schema::hasColumn('profit_analysis_periods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillExistingPeriods(): void
    {
        DB::table('profit_analysis_periods')
            ->orderBy('id')
            ->get()
            ->each(function (object $period): void {
                $rows = DB::table('profit_analysis_sku_summaries')
                    ->where('profit_analysis_period_id', $period->id)
                    ->get();

                $skuRevenueTotal = (float) $rows->sum(fn (object $row): float => (float) $row->revenue);
                $totalRevenue = (float) $period->total_revenue;
                $revenueAdjustment = $totalRevenue - $skuRevenueTotal;
                $sourceTotals = json_decode((string) ($period->source_totals ?? ''), true) ?: [];
                $completedOrderCount = (int) data_get($sourceTotals, 'orders.unique_orders', $period->order_count);
                $analyticsOrderCount = (int) data_get($sourceTotals, 'analytics.orders', $period->order_count);

                $totalCogs = 0.0;
                foreach ($rows as $row) {
                    $originalRevenue = (float) $row->revenue;
                    $share = $skuRevenueTotal > 0 ? $originalRevenue / $skuRevenueTotal : 0;
                    $allocatedAdjustment = $revenueAdjustment * $share;
                    $finalRevenue = $originalRevenue + $allocatedAdjustment;
                    $cogs = (float) $row->cogs;
                    $profit = $finalRevenue - $cogs - (float) $row->allocated_fees - (float) $row->allocated_ad_cost;
                    $netQuantity = (float) $row->net_quantity;

                    DB::table('profit_analysis_sku_summaries')
                        ->where('id', $row->id)
                        ->update([
                            'original_revenue' => $originalRevenue,
                            'allocated_revenue_adjustment' => $allocatedAdjustment,
                            'final_revenue' => $finalRevenue,
                            'revenue' => $finalRevenue,
                            'profit' => $profit,
                            'profit_per_unit' => $netQuantity > 0 ? $profit / $netQuantity : 0,
                            'status' => $profit >= 0 ? 'profit' : 'loss',
                        ]);

                    $totalCogs += $cogs;
                }

                $marketplaceFees = (float) $period->marketplace_fees;
                $adCost = (float) $period->ad_cost;
                $totalCost = $totalCogs + $marketplaceFees + $adCost;
                $profit = $totalRevenue - $totalCost;
                $orderCount = max(1, $completedOrderCount);

                DB::table('profit_analysis_periods')
                    ->where('id', $period->id)
                    ->update([
                        'sku_revenue_total' => $skuRevenueTotal,
                        'revenue_adjustment' => $revenueAdjustment,
                        'completed_order_count' => $completedOrderCount,
                        'analytics_order_count' => $analyticsOrderCount,
                        'order_count' => $completedOrderCount,
                        'cogs' => $totalCogs,
                        'total_cost' => $totalCost,
                        'profit' => $profit,
                        'profit_per_order' => $profit / $orderCount,
                        'ad_breakeven' => $totalRevenue - $marketplaceFees - $totalCogs,
                    ]);
            });
    }
};
