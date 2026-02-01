<?php

namespace App\Services\InventoryLedger;

use App\Constants\TransactionConstants;
use App\Models\InventoryLedger;
use App\Models\Transaction;

class RecalculateLedgerService
{

    /**
     * Recalculate all ledger entries for a product from a specific date.
     * Used after insert/update/delete of transactions.
     */
    public function recalculateFromDate(int $productId, string $fromDate): void
    {
        // Get the ledger state BEFORE the affected date
        $previousLedger = InventoryLedger::whereHas('transaction', function ($query) use ($productId, $fromDate) {
            $query->where('product_id', $productId)->where('transaction_date', '<', $fromDate);
        })->orderByDesc('id')->first();

        // Delete all ledger entries from the affected date onwards for this product
        InventoryLedger::whereHas('transaction', function ($query) use ($productId, $fromDate) {
            $query->where('product_id', $productId)->where('transaction_date', '>=', $fromDate);
        })->forceDelete();

        // Get all transactions from the affected date onwards, ordered by date
        $transactions = Transaction::where('product_id', $productId)
            ->where('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Recalculate and create ledger entries in order
        $currentQuantity = $previousLedger?->quantity_on_hand ?? 0;
        $currentTotalValue = $previousLedger?->total_inventory_value ?? 0;
        $currentAverageCost = $previousLedger?->average_cost_per_unit ?? 0;

        foreach ($transactions as $transaction) {
            $result = $this->calculateForTransaction(
                $transaction,
                $currentQuantity,
                $currentTotalValue,
                $currentAverageCost
            );

            InventoryLedger::create([
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'quantity_on_hand' => $result['quantity_on_hand'],
                'average_cost_per_unit' => $result['average_cost_per_unit'],
                'total_inventory_value' => $result['total_inventory_value'],
                'cost_of_goods_sold' => $result['cost_of_goods_sold'],
            ]);

            // Update running totals for next iteration
            $currentQuantity = $result['quantity_on_hand'];
            $currentTotalValue = $result['total_inventory_value'];
            $currentAverageCost = $result['average_cost_per_unit'];
        }
    }

    /**
     * Recalculate ALL ledger entries for a product from scratch.
     * Useful after delete operations.
     */
    public function recalculateAll(int $productId): void
    {
        // Delete all ledger entries for this product
        InventoryLedger::where('product_id', $productId)->forceDelete();

        // Get all transactions ordered by date
        $transactions = Transaction::where('product_id', $productId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $currentQuantity = 0;
        $currentTotalValue = 0;
        $currentAverageCost = 0;

        foreach ($transactions as $transaction) {
            $result = $this->calculateForTransaction(
                $transaction,
                $currentQuantity,
                $currentTotalValue,
                $currentAverageCost
            );

            InventoryLedger::create([
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'quantity_on_hand' => $result['quantity_on_hand'],
                'average_cost_per_unit' => $result['average_cost_per_unit'],
                'total_inventory_value' => $result['total_inventory_value'],
                'cost_of_goods_sold' => $result['cost_of_goods_sold'],
            ]);

            $currentQuantity = $result['quantity_on_hand'];
            $currentTotalValue = $result['total_inventory_value'];
            $currentAverageCost = $result['average_cost_per_unit'];
        }
    }

    private function calculateForTransaction(
        Transaction $transaction,
        int $previousQuantity,
        float $previousTotalValue,
        float $previousAverageCost
    ): array {
        if ($transaction->transaction_type === TransactionConstants::TYPE_PURCHASE) {
            $newQuantity = $previousQuantity + $transaction->quantity;
            $addedValue = $transaction->quantity * $transaction->unit_price;
            $newTotalValue = $previousTotalValue + $addedValue;
            $newAverageCost = $newQuantity > 0 ? $newTotalValue / $newQuantity : 0;

            return [
                'quantity_on_hand' => $newQuantity,
                'average_cost_per_unit' => $newAverageCost,
                'total_inventory_value' => $newTotalValue,
                'cost_of_goods_sold' => null,
            ];
        }

        // Sale
        $costOfGoodsSold = $transaction->quantity * $previousAverageCost;
        $newQuantity = $previousQuantity - $transaction->quantity;
        $newTotalValue = $previousTotalValue - $costOfGoodsSold;

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
}
