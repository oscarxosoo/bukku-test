<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\CreateTransactionData;
use App\DataTransferObjects\ListTransactionFilter;
use App\DataTransferObjects\UpdateTransactionData;
use App\Exceptions\BusinessValidationException;
use App\Http\Requests\ListTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\Transactions\TransactionService;
use App\UseCases\Transactions\CreateTransactionUseCase;
use App\UseCases\Transactions\DeleteTransactionUseCase;
use App\UseCases\Transactions\UpdateTransactionUseCase;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private CreateTransactionUseCase $createTransactionUseCase,
        private UpdateTransactionUseCase $updateTransactionUseCase,
        private DeleteTransactionUseCase $deleteTransactionUseCase,
        private TransactionService $transactionService
    ) {}

    public function index(ListTransactionRequest $request): JsonResponse
    {
        $filter = ListTransactionFilter::fromArray($request->validated());
        $transactions = $this->transactionService->list($filter);

        return response()->json([
            'data' => $transactions,
        ]);
    }

    /**
     * @throws BusinessValidationException
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = CreateTransactionData::fromArray($request->validated());
        $transaction = $this->createTransactionUseCase->execute($data);

        return response()->json([
            'message' => 'Transaction created successfully.',
            'data' => $transaction,
        ], 201);
    }

    /**
     * @throws BusinessValidationException
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $data = UpdateTransactionData::fromArray($request->validated());
        $transaction = $this->updateTransactionUseCase->execute($transaction, $data);

        return response()->json([
            'message' => 'Transaction updated successfully.',
            'data' => $transaction,
        ]);
    }

    /**
     * @throws BusinessValidationException
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->deleteTransactionUseCase->execute($transaction);

        return response()->json([
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
