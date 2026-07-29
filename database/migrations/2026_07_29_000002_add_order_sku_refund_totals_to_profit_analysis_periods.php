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
            if (! Schema::hasColumn('profit_analysis_periods', 'sku_gross_revenue_total')) {
                $table->decimal('sku_gross_revenue_total', 18, 2)->default(0)->after('settlement_revenue');
            }
            if (! Schema::hasColumn('profit_analysis_periods', 'sku_refund_total')) {
                $table->decimal('sku_refund_total', 18, 2)->default(0)->after('sku_gross_revenue_total');
            }
        });

        DB::table('profit_analysis_periods')
            ->orderBy('id')
            ->get()
            ->each(function (object $period): void {
                $refundTotal = (float) DB::table('profit_analysis_sku_summaries')
                    ->where('profit_analysis_period_id', $period->id)
                    ->sum('refund_amount');
                $skuRevenueTotal = (float) ($period->sku_revenue_total ?? 0);

                DB::table('profit_analysis_periods')
                    ->where('id', $period->id)
                    ->update([
                        'sku_refund_total' => $refundTotal,
                        'sku_gross_revenue_total' => $skuRevenueTotal + $refundTotal,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('profit_analysis_periods', function (Blueprint $table): void {
            foreach (['sku_refund_total', 'sku_gross_revenue_total'] as $column) {
                if (Schema::hasColumn('profit_analysis_periods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
