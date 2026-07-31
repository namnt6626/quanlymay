<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitAnalysisSkuSummary extends Model
{
    protected $fillable = [
        'profit_analysis_period_id',
        'marketplace',
        'seller_sku',
        'fob_sku',
        'product_name',
        'unit_cost',
        'quantity_sold',
        'quantity_returned',
        'net_quantity',
        'original_revenue',
        'revenue',
        'refund_amount',
        'allocated_revenue_adjustment',
        'final_revenue',
        'cogs',
        'allocated_fees',
        'allocated_ad_cost',
        'profit',
        'profit_per_unit',
        'status',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(ProfitAnalysisPeriod::class, 'profit_analysis_period_id');
    }
}
