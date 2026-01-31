<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\CreateTransactionData;
use App\DataTransferObjects\ListTransactionFilter;
use App\Exceptions\BusinessValidationException;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\Transactions\TransactionService;
use App\UseCases\Transactions\CreateTransactionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private CreateTransactionUseCase $createTransactionUseCase,
        private TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filter = ListTransactionFilter::fromRequest($request);
        $transactions = $this->transactionService->list($filter);

        return response()->json([
            'data' => $transactions,
        ]);
    }

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
}
