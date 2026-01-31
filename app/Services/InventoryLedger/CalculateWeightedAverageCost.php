<?php

namespace App\Services\InventoryLedger;

use App\Constants\TransactionConstants;
use App\Models\InventoryLedger;
use App\Models\Transaction;

class CalculateWeightedAverageCost
{
    public function calculate(Transaction $transaction): array
    {
        $previousLedger = $this->getPreviousLedger($transaction->product_id);

        $previousQuantity = $previousLedger?->quantity_on_hand ?? 0;
        $previousTotalValue = $previousLedger?->total_inventory_value ?? 0;
        $previousAverageCost = $previousLedger?->average_cost_per_unit ?? 0;

        if ($transaction->transaction_type === TransactionConstants::TYPE_PURCHASE) {
            return $this->calculateForPurchase(
                $previousQuantity,
                $previousTotalValue,
                $transaction->quantity,
                $transaction->unit_price
            );
        }

        return $this->calculateForSale(
            $previousQuantity,
            $previousTotalValue,
            $previousAverageCost,
            $transaction->quantity
        );
    }

    private function calculateForPurchase(
        int $previousQuantity,
        float $previousTotalValue,
        int $purchaseQuantity,
        float $purchaseUnitPrice
    ): array {
        $newQuantity = $previousQuantity + $purchaseQuantity;
        $addedValue = $purchaseQuantity * $purchaseUnitPrice;
        $newTotalValue = $previousTotalValue + $addedValue;
        $newAverageCost = $newQuantity > 0 ? $newTotalValue / $newQuantity : 0;

        return [
            'quantity_on_hand' => $newQuantity,
            'average_cost_per_unit' => $newAverageCost,
            'total_inventory_value' => $newTotalValue,
            'cost_of_goods_sold' => null,
        ];
    }

    private function calculateForSale(
        int $previousQuantity,
        float $previousTotalValue,
        float $previousAverageCost,
        int $saleQuantity
    ): array {
        $costOfGoodsSold = $saleQuantity * $previousAverageCost;
        $newQuantity = $previousQuantity - $saleQuantity;
        $newTotalValue = $previousTotalValue - $costOfGoodsSold;

        // Reset average cost and total value when inventory becomes empty
        if ($newQuantity === 0) {
            return [
                'quantity_on_hand' => 0,
                'average_cost_per_unit' => 0,
                'total_inventory_value' => 0,
                'cost_of_goods_sold' => $costOfGoodsSold,
            ];
        }

        return [
            'quantity_on_hand' => $newQuantity,
            'average_cost_per_unit' => $previousAverageCost,
            'total_inventory_value' => $newTotalValue,
            'cost_of_goods_sold' => $costOfGoodsSold,
        ];
    }

    private function getPreviousLedger(int $productId): ?InventoryLedger
    {
        return InventoryLedger::where('product_id', $productId)
            ->latest('id')
            ->first();
    }
}
