<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\CreateTransactionData;
use App\Exceptions\BusinessValidationException;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\Transactions\TransactionService;
use App\UseCases\Transactions\CreateTransactionUseCase;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private CreateTransactionUseCase $createTransactionUseCase,
        private TransactionService $transactionService
    ) {}

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $data = CreateTransactionData::fromArray($request->validated());
            $transaction = $this->createTransactionUseCase->execute($data);

            return response()->json([
                'message' => 'Transaction created successfully.',
                'data' => $transaction,
            ], 201);
        } catch (BusinessValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => [
                    $e->getField() => [$e->getMessage()],
                ],
            ], 422);
        }
    }

    public function purchases(): JsonResponse
    {
        $purchases = $this->transactionService->listPurchases();

        return response()->json([
            'data' => $purchases,
        ]);
    }

    public function sales(): JsonResponse
    {
        $sales = $this->transactionService->listSales();

        return response()->json([
            'data' => $sales,
        ]);
    }
}
