<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $product_id
 * @property int $quantity_on_hand
 * @property numeric $average_cost_per_unit
 * @property numeric $total_inventory_value
 * @property numeric|null $cost_of_goods_sold
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Transaction $transaction
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereAverageCostPerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereCostOfGoodsSold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereQuantityOnHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereTotalInventoryValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryLedger withoutTrashed()
 * @mixin \Eloquent
 */
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
        'average_cost_per_unit' => 'decimal:2',
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
