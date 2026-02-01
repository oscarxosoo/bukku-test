<?php

namespace App\DataTransferObjects;

class LedgerCalculationResult
{
    public function __construct(
        public readonly int $quantityOnHand,
        public readonly float $averageCostPerUnit,
        public readonly float $totalInventoryValue,
        public readonly ?float $costOfGoodsSold,
    ) {}
}
