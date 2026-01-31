<?php

namespace App\Services\Transactions;

use App\DataTransferObjects\ListTransactionFilter;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function list(ListTransactionFilter $filter): Collection
    {
        return Transaction::query()
            ->when($filter->type, fn ($query) => $query->where('transaction_type', $filter->type))
            ->when($filter->productId, fn ($query) => $query->where('product_id', $filter->productId))
            ->with('product', 'inventoryLedger')
            ->orderBy('transaction_date')
            ->get();
    }
}
