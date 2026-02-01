<?php

namespace App\Repositories;

use App\Models\InventoryLedger;
use Illuminate\Support\Collection;

class InventoryLedgerRepository
{
    public function getLastLedgerBeforeDate(int $productId, string $date): ?InventoryLedger
    {
        return InventoryLedger::whereHas('transaction', function ($query) use ($productId, $date) {
            $query->where('product_id', $productId)->where('transaction_date', '<', $date);
        })->orderByDesc('id')->first();
    }

    public function deleteFromDateOnwards(int $productId, string $fromDate): void
    {
        InventoryLedger::whereHas('transaction', function ($query) use ($productId, $fromDate) {
            $query->where('product_id', $productId)->where('transaction_date', '>=', $fromDate);
        })->forceDelete();
    }

    public function create(array $data): InventoryLedger
    {
        return InventoryLedger::create($data);
    }
}
