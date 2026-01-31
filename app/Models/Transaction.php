<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'transaction_type',
        'transaction_date',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLedger(): HasOne
    {
        return $this->hasOne(InventoryLedger::class);
    }
}
