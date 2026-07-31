<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfitAnalysisSkuMap extends Model
{
    protected $fillable = [
        'marketplace',
        'seller_sku',
        'fob_sku',
        'fob_code',
        'product_name',
        'unit_cost',
        'source',
        'status',
        'note',
    ];
}
