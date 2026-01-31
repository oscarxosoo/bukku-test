<?php

namespace App\UseCases\Transactions;

use App\Constants\TransactionConstants;
use App\DataTransferObjects\CreateTransactionData;
use App\Exceptions\BusinessValidationException;
use App\Models\Transaction;
use App\Services\InventoryLedger\InventoryLedgerService;
use App\Services\Transactions\TransactionRulesService;
use App\Services\Transactions\TransactionService;
use Illuminate\Support\Facades\DB;

class CreateTransactionUseCase
{
    public function __construct(
        private TransactionService $transactionService,
        private InventoryLedgerService $inventoryLedgerService,
        private TransactionRulesService $transactionRulesService
    ) {}

    /**
     * @throws BusinessValidationException
     */
    public function execute(CreateTransactionData $data): Transaction
    {
        try {
            $this->transactionRulesService->validateDateSequence($data->productId, $data->transactionDate);

            if ($data->transactionType === TransactionConstants::TYPE_SALE) {
                $this->transactionRulesService->validateSufficientStock($data->productId, $data->quantity);
            }

            return DB::transaction(function () use ($data) {
                $transaction = $this->transactionService->create($data->toArray());
                $this->inventoryLedgerService->createFromTransaction($transaction);

                return $transaction->load('inventoryLedger');
            });
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new BusinessValidationException($e->getMessage(), 'general');
        }
    }
}
