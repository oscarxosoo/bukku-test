<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionRepository
{
    public function getFromDateOnwards(int $productId, string $fromDate): Collection
    {
        return Transaction::where('product_id', $productId)
            ->where('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }
}
