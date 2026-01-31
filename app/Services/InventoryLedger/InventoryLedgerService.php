<?php

namespace App\Services\InventoryLedger;

use App\Models\InventoryLedger;
use App\Models\Transaction;

class InventoryLedgerService
{
    public function __construct(
        private CalculateWeightedAverageCost $wacCalculator
    ) {}

    public function createFromTransaction(Transaction $transaction): InventoryLedger
    {
        $wacResult = $this->wacCalculator->calculate($transaction);

        return InventoryLedger::create([
            'transaction_id' => $transaction->id,
            'product_id' => $transaction->product_id,
            'quantity_on_hand' => $wacResult['quantity_on_hand'],
            'average_cost_per_unit' => $wacResult['average_cost_per_unit'],
            'total_inventory_value' => $wacResult['total_inventory_value'],
            'cost_of_goods_sold' => $wacResult['cost_of_goods_sold'],
        ]);
    }
}
