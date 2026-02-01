<?php

namespace App\Services\InventoryLedger;

use App\Constants\TransactionConstants;
use App\DataTransferObjects\LedgerCalculationResult;

class CalculateWacService
{
    public function calculate(
        string $transactionType,
        int $quantity,
        float $unitPrice,
        int $previousQuantity,
        float $previousTotalValue,
        float $previousAverageCost
    ): LedgerCalculationResult {
        if ($transactionType === TransactionConstants::TYPE_PURCHASE) {
            return $this->calculatePurchase(
                $quantity,
                $unitPrice,
                $previousQuantity,
                $previousTotalValue
            );
        }

        return $this->calculateSale(
            $quantity,
            $previousQuantity,
            $previousTotalValue,
            $previousAverageCost
        );
    }

    private function calculatePurchase(
        int $quantity,
        float $unitPrice,
        int $previousQuantity,
        float $previousTotalValue
    ): LedgerCalculationResult {
        $newQuantity = $previousQuantity + $quantity;
        $addedValue = $quantity * $unitPrice;
        $newTotalValue = round($previousTotalValue + $addedValue, 2);
        $newAverageCost = $newQuantity > 0 ? round($newTotalValue / $newQuantity, 2) : 0;

        return new LedgerCalculationResult(
            quantityOnHand: $newQuantity,
            averageCostPerUnit: $newAverageCost,
            totalInventoryValue: $newTotalValue,
            costOfGoodsSold: null,
        );
    }

    private function calculateSale(
        int $quantity,
        int $previousQuantity,
        float $previousTotalValue,
        float $previousAverageCost
    ): LedgerCalculationResult {
        $costOfGoodsSold = round($quantity * $previousAverageCost, 2);
        $newQuantity = $previousQuantity - $quantity;
        $newTotalValue = round($previousTotalValue - $costOfGoodsSold, 2);

        if ($newQuantity === 0) {
            return new LedgerCalculationResult(
                quantityOnHand: 0,
                averageCostPerUnit: 0,
                totalInventoryValue: 0,
                costOfGoodsSold: $costOfGoodsSold,
            );
        }

        return new LedgerCalculationResult(
            quantityOnHand: $newQuantity,
            averageCostPerUnit: $previousAverageCost,
            totalInventoryValue: $newTotalValue,
            costOfGoodsSold: $costOfGoodsSold,
        );
    }
}
