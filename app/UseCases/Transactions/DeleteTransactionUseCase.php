<?php

namespace App\UseCases\Transactions;

use App\Exceptions\Domain\ServiceException;
use App\Exceptions\Http\BusinessValidationException;
use App\Models\Transaction;
use App\Services\InventoryLedger\RecalculateLedgerService;
use App\Services\Transactions\TransactionRulesService;
use Illuminate\Support\Facades\DB;

class DeleteTransactionUseCase
{
    public function __construct(
        private RecalculateLedgerService $recalculateLedgerService,
        private TransactionRulesService $transactionRulesService
    ) {}


    /**
     * @throws BusinessValidationException
     */
    public function execute(Transaction $transaction): void
    {
        $productId = $transaction->product_id;
        $transactionDate = $transaction->transaction_date;

        DB::transaction(function () use ($transaction, $productId, $transactionDate) {
            try {
                $transaction->delete();
                $transaction->inventoryLedger?->delete();

                $this->transactionRulesService->validateNoNegativeStock($productId);

                $this->recalculateLedgerService->recalculateFromDate($productId, $transactionDate);
            } catch (ServiceException $e) {
                throw new BusinessValidationException($e->getMessage());
            }
        });
    }
}
