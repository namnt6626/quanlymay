<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitAnalysisPeriod extends Model
{
    protected $fillable = [
        'period_month',
        'period_start',
        'period_end',
        'label',
        'sku_count',
        'missing_cost_count',
        'order_count',
        'item_count',
        'gmv',
        'settlement_revenue',
        'marketplace_fees',
        'ad_cost',
        'cogs',
        'total_revenue',
        'total_cost',
        'profit',
        'profit_per_order',
        'ad_breakeven',
        'source_totals',
        'confirmed_by',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'confirmed_at' => 'datetime',
            'source_totals' => 'array',
        ];
    }

    public function skuSummaries(): HasMany
    {
        return $this->hasMany(ProfitAnalysisSkuSummary::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
