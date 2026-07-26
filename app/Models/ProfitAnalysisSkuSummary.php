<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitAnalysisSkuSummary extends Model
{
    protected $fillable = [
        'profit_analysis_period_id',
        'seller_sku',
        'fob_sku',
        'product_name',
        'unit_cost',
        'quantity_sold',
        'quantity_returned',
        'net_quantity',
        'revenue',
        'refund_amount',
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
