<?php

namespace App\UseCases\Transactions;

use App\Constants\TransactionConstants;
use App\DataTransferObjects\CreateTransactionData;
use App\Exceptions\Domain\ServiceException;
use App\Exceptions\Http\BusinessValidationException;
use App\Models\Transaction;
use App\Services\InventoryLedger\RecalculateLedgerService;
use App\Services\Transactions\TransactionRulesService;
use App\Services\Transactions\TransactionService;
use Illuminate\Support\Facades\DB;

class CreateTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private RecalculateLedgerService $recalculateLedgerService,
        private TransactionRulesService $transactionRulesService
    ) {}


    /**
     * @throws BusinessValidationException
     */
    public function execute(CreateTransactionData $data): Transaction
    {
        try {
            if ($data->transactionType === TransactionConstants::TYPE_SALE) {
                $this->transactionRulesService->validateSufficientStockAtDate(
                    $data->productId,
                    $data->quantity,
                    $data->transactionDate
                );
            }

            return DB::transaction(function () use ($data) {
                $transaction = $this->transactionService->create($data->toArray());

                $this->transactionRulesService->validateNoNegativeStock($data->productId);

                $this->recalculateLedgerService->recalculateFromDate(
                    $data->productId,
                    $data->transactionDate
                );

                return $transaction->load('inventoryLedger');
            });
        } catch (ServiceException $e) {
            throw new BusinessValidationException($e->getMessage());
        }
    }
}
