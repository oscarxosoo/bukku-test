<?php

namespace App\Services\InventoryLedger;

use App\Repositories\InventoryLedgerRepository;
use App\Repositories\TransactionRepository;

class RecalculateLedgerService
{
    public function __construct(
        private CalculateWacService $calculateWacService,
        private InventoryLedgerRepository $ledgerRepository,
        private TransactionRepository $transactionRepository
    ) {}

    /**
     * Recalculate all ledger entries for a product from a specific date.
     * Used after insert/update/delete of transactions.
     */
    public function recalculateFromDate(int $productId, string $fromDate): void
    {
        $previousLedger = $this->ledgerRepository->getLastLedgerBeforeDate($productId, $fromDate);

        $this->ledgerRepository->deleteFromDateOnwards($productId, $fromDate);

        $transactions = $this->transactionRepository->getFromDateOnwards($productId, $fromDate);

        $currentQuantity = $previousLedger?->quantity_on_hand ?? 0;
        $currentTotalValue = $previousLedger?->total_inventory_value ?? 0;
        $currentAverageCost = $previousLedger?->average_cost_per_unit ?? 0;

        foreach ($transactions as $transaction) {
            $result = $this->calculateWacService->calculate(
                $transaction->transaction_type,
                $transaction->quantity,
                $transaction->unit_price,
                $currentQuantity,
                $currentTotalValue,
                $currentAverageCost
            );

            $this->ledgerRepository->create([
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'quantity_on_hand' => $result->quantityOnHand,
                'average_cost_per_unit' => $result->averageCostPerUnit,
                'total_inventory_value' => $result->totalInventoryValue,
                'cost_of_goods_sold' => $result->costOfGoodsSold,
            ]);

            $currentQuantity = $result->quantityOnHand;
            $currentTotalValue = $result->totalInventoryValue;
            $currentAverageCost = $result->averageCostPerUnit;
        }
    }
}
