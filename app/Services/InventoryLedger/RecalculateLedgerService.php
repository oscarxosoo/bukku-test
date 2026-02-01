<?php

namespace App\Services\InventoryLedger;

use App\Constants\TransactionConstants;
use App\DataTransferObjects\LedgerCalculationResult;
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
                'quantity_on_hand' => $result->quantityOnHand,
                'average_cost_per_unit' => $result->averageCostPerUnit,
                'total_inventory_value' => $result->totalInventoryValue,
                'cost_of_goods_sold' => $result->costOfGoodsSold,
            ]);

            // Update running totals for next iteration
            $currentQuantity = $result->quantityOnHand;
            $currentTotalValue = $result->totalInventoryValue;
            $currentAverageCost = $result->averageCostPerUnit;
        }
    }

    private function calculateForTransaction(
        Transaction $transaction,
        int $previousQuantity,
        float $previousTotalValue,
        float $previousAverageCost
    ): LedgerCalculationResult {
        if ($transaction->transaction_type === TransactionConstants::TYPE_PURCHASE) {
            $newQuantity = $previousQuantity + $transaction->quantity;
            $addedValue = $transaction->quantity * $transaction->unit_price;
            $newTotalValue = round($previousTotalValue + $addedValue, 2);
            $newAverageCost = $newQuantity > 0 ? round($newTotalValue / $newQuantity, 2) : 0;

            return new LedgerCalculationResult(
                quantityOnHand: $newQuantity,
                averageCostPerUnit: $newAverageCost,
                totalInventoryValue: $newTotalValue,
                costOfGoodsSold: null,
            );
        }

        // Sale
        $costOfGoodsSold = round($transaction->quantity * $previousAverageCost, 2);
        $newQuantity = $previousQuantity - $transaction->quantity;
        $newTotalValue = round($previousTotalValue - $costOfGoodsSold, 2);

        if ($newQuantity === 0) {
            return new LedgerCalculationResult(
                quantityOnHand: 0,
                averageCostPerUnit: 0,
                totalInventoryValue: 0,
                costOfGoodsSold: $costOfGoodsSold,
            );
        }

        return new LedgerCalculationResult(
            quantityOnHand: $newQuantity,
            averageCostPerUnit: $previousAverageCost,
            totalInventoryValue: $newTotalValue,
            costOfGoodsSold: $costOfGoodsSold,
        );
    }
}
