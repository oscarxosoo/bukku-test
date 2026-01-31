<?php

namespace App\Services\Transactions;

use App\Constants\TransactionConstants;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function listPurchases(): Collection
    {
        return Transaction::where('transaction_type', TransactionConstants::TYPE_PURCHASE)
            ->with('product', 'inventoryLedger')
            ->orderBy('transaction_date')
            ->get();
    }

    public function listSales(): Collection
    {
        return Transaction::where('transaction_type', TransactionConstants::TYPE_SALE)
            ->with('product', 'inventoryLedger')
            ->orderBy('transaction_date')
            ->get();
    }
}
