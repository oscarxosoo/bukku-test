<?php

namespace App\Services\Transactions;

use App\Constants\TransactionConstants;
use App\Exceptions\BusinessValidationException;
use App\Models\InventoryLedger;
use App\Models\Transaction;

class TransactionRulesService
{
    /**
     * Validate sufficient stock at a specific date for a sale.
     * Calculates what the stock would be just before the given date.
     *
     * @throws BusinessValidationException
     */
    public function validateSufficientStockAtDate(int $productId, int $quantity, string $transactionDate): void
    {
        $stockAtDate = $this->calculateStockAtDate($productId, $transactionDate);

        if ($stockAtDate === 0) {
            throw new BusinessValidationException(
                'Inventory is empty at this date. No stock available for this product.',
                'quantity'
            );
        }

        if ($quantity > $stockAtDate) {
            throw new BusinessValidationException(
                sprintf('Insufficient stock at this date. Available: %d, Requested: %d.', $stockAtDate, $quantity),
                'quantity'
            );
        }
    }

    /**
     * Calculate what the stock would be just before a specific date.
     */
    private function calculateStockAtDate(int $productId, string $date): int
    {
        // Get the last ledger entry before this date
        $ledgerBeforeDate = InventoryLedger::whereHas('transaction', function ($query) use ($productId, $date) {
            $query->where('product_id', $productId)
                ->where('transaction_date', '<', $date);
        })
            ->orderByDesc('id')
            ->first();

        return $ledgerBeforeDate?->quantity_on_hand ?? 0;
    }

    /**
     * Validate that recalculation won't cause negative stock.
     * Call this AFTER inserting/updating a transaction but BEFORE committing.
     *
     * @throws BusinessValidationException
     */
    public function validateNoNegativeStock(int $productId): void
    {
        $transactions = Transaction::where('product_id', $productId)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $currentQuantity = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->transaction_type === TransactionConstants::TYPE_PURCHASE) {
                $currentQuantity += $transaction->quantity;
            } else {
                $currentQuantity -= $transaction->quantity;

                if ($currentQuantity < 0) {
                    throw new BusinessValidationException(
                        sprintf(
                            'This transaction would cause negative stock on %s. Available at that date: %d, Requested: %d.',
                            $transaction->transaction_date,
                            $currentQuantity + $transaction->quantity,
                            $transaction->quantity
                        ),
                        'quantity'
                    );
                }
            }
        }
    }
}
