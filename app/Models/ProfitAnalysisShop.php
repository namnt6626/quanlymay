<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfitAnalysisShop extends Model
{
    protected $fillable = [
        'marketplace',
        'name',
        'normalized_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(ProfitAnalysisPeriod::class, 'shop_id');
    }

    public function getMarketplaceLabelAttribute(): string
    {
        return match ($this->marketplace) {
            'shopee' => 'Shopee',
            default => 'TikTok',
        };
    }
}
