<?php

namespace App\Services\Transactions;

use App\Exceptions\BusinessValidationException;
use App\Models\InventoryLedger;
use App\Models\Transaction;

class TransactionRulesService
{
    /**
     * @throws BusinessValidationException
     */
    public function validateDateSequence(int $productId, string $transactionDate): void
    {
        $latestTransaction = Transaction::where('product_id', $productId)
            ->orderBy('transaction_date', 'desc')
            ->first();

        if ($latestTransaction && $transactionDate <= $latestTransaction->transaction_date->format('Y-m-d')) {
            throw new BusinessValidationException(
                sprintf('Transaction date must be after the latest transaction date (%s) for this product.', $latestTransaction->transaction_date->format('Y-m-d')),
                'transaction_date'
            );
        }
    }

    /**
     * @throws BusinessValidationException
     */
    public function validateSufficientStock(int $productId, int $quantity): void
    {
        $latestLedger = InventoryLedger::where('product_id', $productId)
            ->latest('id')
            ->first();

        $availableQuantity = $latestLedger?->quantity_on_hand ?? 0;

        if ($availableQuantity === 0) {
            throw new BusinessValidationException(
                'Inventory is empty. No stock available for this product.',
                'quantity'
            );
        }

        if ($quantity > $availableQuantity) {
            throw new BusinessValidationException(
                sprintf('Insufficient stock. Available: %d, Requested: %d.', $availableQuantity, $quantity),
                'quantity'
            );
        }
    }
}
