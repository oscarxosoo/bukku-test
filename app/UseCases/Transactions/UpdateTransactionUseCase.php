<?php

namespace App\UseCases\Transactions;

use App\DataTransferObjects\UpdateTransactionData;
use App\Exceptions\BusinessValidationException;
use App\Models\Transaction;
use App\Services\InventoryLedger\RecalculateLedgerService;
use App\Services\Transactions\TransactionRulesService;
use Illuminate\Support\Facades\DB;

class UpdateTransactionUseCase
{
    public function __construct(
        private RecalculateLedgerService $recalculateLedgerService,
        private TransactionRulesService $transactionRulesService
    ) {}

    /**
     * @throws BusinessValidationException
     */
    public function execute(Transaction $transaction, UpdateTransactionData $data): Transaction
    {
        $oldDate = $transaction->transaction_date;
        $newDate = $data->transactionDate ?? $oldDate;
        $productId = $transaction->product_id;

        return DB::transaction(function () use ($transaction, $data, $oldDate, $newDate, $productId) {
            $transaction->update(array_filter($data->toArray(), fn ($value) => $value !== null));

            $this->transactionRulesService->validateNoNegativeStock($productId);

            $earliestDate = min($oldDate, $newDate);
            $this->recalculateLedgerService->recalculateFromDate($productId, $earliestDate);

            return $transaction->fresh()->load('inventoryLedger');
        });
    }
}
