<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLedger extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_ledger';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity_on_hand',
        'average_cost_per_unit',
        'total_inventory_value',
        'cost_of_goods_sold',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'average_cost_per_unit' => 'decimal:4',
        'total_inventory_value' => 'decimal:2',
        'cost_of_goods_sold' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
