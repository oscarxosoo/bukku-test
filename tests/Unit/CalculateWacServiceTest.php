<?php

namespace Tests\Unit;

use App\Constants\TransactionConstants;
use App\Services\InventoryLedger\CalculateWacService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WAC (Weighted Average Cost) calculation logic.
 * Tests pure calculation without database interaction.
 */
class CalculateWacServiceTest extends TestCase
{
    private CalculateWacService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CalculateWacService();
    }

    /**
     * Test the exact scenario from the Bukku PDF assignment.
     */
    public function test_pdf_scenario_step_by_step(): void
    {
        // Step 1: Buy 150 units at RM2 each
        // Expected: total = 300, avg = 2.00
        $result1 = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 150,
            unitPrice: 2.00,
            previousQuantity: 0,
            previousTotalValue: 0,
            previousAverageCost: 0
        );

        $this->assertEquals(150, $result1->quantityOnHand);
        $this->assertEquals(300.00, $result1->totalInventoryValue);
        $this->assertEquals(2.00, $result1->averageCostPerUnit);
        $this->assertNull($result1->costOfGoodsSold);

        // Step 2: Buy 10 units at RM1.50 each
        // Expected: total = 315, qty = 160, avg = 1.97
        $result2 = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 10,
            unitPrice: 1.50,
            previousQuantity: 150,
            previousTotalValue: 300.00,
            previousAverageCost: 2.00
        );

        $this->assertEquals(160, $result2->quantityOnHand);
        $this->assertEquals(315.00, $result2->totalInventoryValue);
        $this->assertEquals(1.97, $result2->averageCostPerUnit); // 315/160 = 1.96875 → 1.97

        // Step 3: Sell 5 units
        // Expected: COGS = 5 × 1.97 = 9.85, remaining = 305.15, qty = 155
        $result3 = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_SALE,
            quantity: 5,
            unitPrice: 10.00, // Selling price doesn't affect COGS
            previousQuantity: 160,
            previousTotalValue: 315.00,
            previousAverageCost: 1.97
        );

        $this->assertEquals(155, $result3->quantityOnHand);
        $this->assertEquals(9.85, $result3->costOfGoodsSold);
        $this->assertEquals(305.15, $result3->totalInventoryValue);
        $this->assertEquals(1.97, $result3->averageCostPerUnit); // Avg cost unchanged after sale
    }

    public function test_purchase_from_zero_inventory(): void
    {
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 100,
            unitPrice: 10.00,
            previousQuantity: 0,
            previousTotalValue: 0,
            previousAverageCost: 0
        );

        $this->assertEquals(100, $result->quantityOnHand);
        $this->assertEquals(1000.00, $result->totalInventoryValue);
        $this->assertEquals(10.00, $result->averageCostPerUnit);
        $this->assertNull($result->costOfGoodsSold);
    }

    public function test_purchase_adds_to_existing_inventory(): void
    {
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 50,
            unitPrice: 20.00,
            previousQuantity: 100,
            previousTotalValue: 1000.00,
            previousAverageCost: 10.00
        );

        // 100 + 50 = 150 units
        // 1000 + 1000 = 2000 value
        // 2000 / 150 = 13.33 avg
        $this->assertEquals(150, $result->quantityOnHand);
        $this->assertEquals(2000.00, $result->totalInventoryValue);
        $this->assertEquals(13.33, $result->averageCostPerUnit);
    }

    public function test_weighted_average_calculation(): void
    {
        // 100 units @ $10 = $1000
        $result1 = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 100,
            unitPrice: 10.00,
            previousQuantity: 0,
            previousTotalValue: 0,
            previousAverageCost: 0
        );

        // Add 100 units @ $20 = $2000
        // Total: 200 units, $3000, avg = $15
        $result2 = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 100,
            unitPrice: 20.00,
            previousQuantity: $result1->quantityOnHand,
            previousTotalValue: $result1->totalInventoryValue,
            previousAverageCost: $result1->averageCostPerUnit
        );

        $this->assertEquals(200, $result2->quantityOnHand);
        $this->assertEquals(3000.00, $result2->totalInventoryValue);
        $this->assertEquals(15.00, $result2->averageCostPerUnit);
    }

    public function test_sale_calculates_cogs_using_average_cost(): void
    {
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_SALE,
            quantity: 30,
            unitPrice: 25.00, // Selling price - should not affect COGS
            previousQuantity: 100,
            previousTotalValue: 1500.00,
            previousAverageCost: 15.00
        );

        // COGS = 30 × 15 = 450
        // Remaining: 70 units, $1050
        $this->assertEquals(70, $result->quantityOnHand);
        $this->assertEquals(450.00, $result->costOfGoodsSold);
        $this->assertEquals(1050.00, $result->totalInventoryValue);
        $this->assertEquals(15.00, $result->averageCostPerUnit); // Unchanged
    }

    public function test_sale_does_not_change_average_cost(): void
    {
        $avgCost = 12.50;

        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_SALE,
            quantity: 10,
            unitPrice: 20.00,
            previousQuantity: 100,
            previousTotalValue: 1250.00,
            previousAverageCost: $avgCost
        );

        $this->assertEquals($avgCost, $result->averageCostPerUnit);
    }

    public function test_selling_all_inventory_resets_to_zero(): void
    {
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_SALE,
            quantity: 100,
            unitPrice: 15.00,
            previousQuantity: 100,
            previousTotalValue: 1000.00,
            previousAverageCost: 10.00
        );

        $this->assertEquals(0, $result->quantityOnHand);
        $this->assertEquals(0, $result->totalInventoryValue);
        $this->assertEquals(0, $result->averageCostPerUnit);
        $this->assertEquals(1000.00, $result->costOfGoodsSold);
    }

    public function test_cogs_is_null_for_purchase(): void
    {
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 50,
            unitPrice: 10.00,
            previousQuantity: 0,
            previousTotalValue: 0,
            previousAverageCost: 0
        );

        $this->assertNull($result->costOfGoodsSold);
    }

    public function test_rounding_to_two_decimal_places(): void
    {
        // 100 units @ $3 = $300
        // 7 units @ $4 = $28
        // Total: 107 units, $328
        // Avg: 328/107 = 3.0654... → should round to 3.07
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_PURCHASE,
            quantity: 7,
            unitPrice: 4.00,
            previousQuantity: 100,
            previousTotalValue: 300.00,
            previousAverageCost: 3.00
        );

        $this->assertEquals(107, $result->quantityOnHand);
        $this->assertEquals(328.00, $result->totalInventoryValue);
        $this->assertEquals(3.07, $result->averageCostPerUnit);
    }

    public function test_sale_cogs_rounded_correctly(): void
    {
        // Sell 3 units at avg cost of 3.33
        // COGS = 3 × 3.33 = 9.99
        $result = $this->calculator->calculate(
            transactionType: TransactionConstants::TYPE_SALE,
            quantity: 3,
            unitPrice: 5.00,
            previousQuantity: 10,
            previousTotalValue: 33.30,
            previousAverageCost: 3.33
        );

        $this->assertEquals(9.99, $result->costOfGoodsSold);
    }

    public function test_multiple_transactions_chain(): void
    {
        // Simulate a sequence of transactions
        $qty = 0;
        $value = 0.0;
        $avg = 0.0;

        // Purchase 100 @ $10
        $r1 = $this->calculator->calculate(TransactionConstants::TYPE_PURCHASE, 100, 10.00, $qty, $value, $avg);
        $qty = $r1->quantityOnHand;
        $value = $r1->totalInventoryValue;
        $avg = $r1->averageCostPerUnit;

        $this->assertEquals(100, $qty);
        $this->assertEquals(1000.00, $value);
        $this->assertEquals(10.00, $avg);

        // Purchase 50 @ $14
        $r2 = $this->calculator->calculate(TransactionConstants::TYPE_PURCHASE, 50, 14.00, $qty, $value, $avg);
        $qty = $r2->quantityOnHand;
        $value = $r2->totalInventoryValue;
        $avg = $r2->averageCostPerUnit;

        $this->assertEquals(150, $qty);
        $this->assertEquals(1700.00, $value);
        $this->assertEquals(11.33, $avg); // 1700/150

        // Sell 30
        $r3 = $this->calculator->calculate(TransactionConstants::TYPE_SALE, 30, 20.00, $qty, $value, $avg);
        $qty = $r3->quantityOnHand;
        $value = $r3->totalInventoryValue;
        $avg = $r3->averageCostPerUnit;

        $this->assertEquals(120, $qty);
        $this->assertEquals(339.90, $r3->costOfGoodsSold); // 30 × 11.33
        $this->assertEquals(1360.10, $value); // 1700 - 339.90
        $this->assertEquals(11.33, $avg); // Unchanged

        // Purchase 80 @ $12
        $r4 = $this->calculator->calculate(TransactionConstants::TYPE_PURCHASE, 80, 12.00, $qty, $value, $avg);

        $this->assertEquals(200, $r4->quantityOnHand);
        $this->assertEquals(2320.10, $r4->totalInventoryValue);
        $this->assertEquals(11.60, $r4->averageCostPerUnit); // 2320.10/200
    }
}
