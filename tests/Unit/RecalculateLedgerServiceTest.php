<?php

namespace Tests\Unit;

use App\Constants\TransactionConstants;
use App\DataTransferObjects\LedgerCalculationResult;
use App\Models\InventoryLedger;
use App\Repositories\InventoryLedgerRepository;
use App\Repositories\TransactionRepository;
use App\Services\InventoryLedger\CalculateWacService;
use App\Services\InventoryLedger\RecalculateLedgerService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class RecalculateLedgerServiceTest extends TestCase
{
    private MockInterface $calculateWacService;
    private MockInterface $ledgerRepository;
    private MockInterface $transactionRepository;
    private RecalculateLedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculateWacService = Mockery::mock(CalculateWacService::class);
        $this->ledgerRepository = Mockery::mock(InventoryLedgerRepository::class);
        $this->transactionRepository = Mockery::mock(TransactionRepository::class);

        $this->service = new RecalculateLedgerService(
            $this->calculateWacService,
            $this->ledgerRepository,
            $this->transactionRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_recalculate_from_date_with_no_previous_ledger(): void
    {
        $productId = 1;
        $fromDate = '2022-01-01';

        // Create mock transaction
        $transaction = $this->createMockTransaction(1, $productId, TransactionConstants::TYPE_PURCHASE, 100, 10.00);

        // No previous ledger
        $this->ledgerRepository
            ->shouldReceive('getLastLedgerBeforeDate')
            ->with($productId, $fromDate)
            ->once()
            ->andReturn(null);

        // Delete existing ledger entries
        $this->ledgerRepository
            ->shouldReceive('deleteFromDateOnwards')
            ->with($productId, $fromDate)
            ->once();

        // Return transactions
        $this->transactionRepository
            ->shouldReceive('getFromDateOnwards')
            ->with($productId, $fromDate)
            ->once()
            ->andReturn(new Collection([$transaction]));

        // Calculate WAC
        $calculationResult = new LedgerCalculationResult(
            quantityOnHand: 100,
            averageCostPerUnit: 10.00,
            totalInventoryValue: 1000.00,
            costOfGoodsSold: null
        );

        $this->calculateWacService
            ->shouldReceive('calculate')
            ->with(TransactionConstants::TYPE_PURCHASE, 100, 10.00, 0, 0, 0)
            ->once()
            ->andReturn($calculationResult);

        // Create new ledger entry
        $this->ledgerRepository
            ->shouldReceive('create')
            ->with([
                'transaction_id' => 1,
                'product_id' => $productId,
                'quantity_on_hand' => 100,
                'average_cost_per_unit' => 10.00,
                'total_inventory_value' => 1000.00,
                'cost_of_goods_sold' => null,
            ])
            ->once();

        $this->service->recalculateFromDate($productId, $fromDate);

        // Mockery will verify the expectations
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function test_recalculate_from_date_with_previous_ledger(): void
    {
        $productId = 1;
        $fromDate = '2022-01-05';

        $transaction = $this->createMockTransaction(2, $productId, TransactionConstants::TYPE_PURCHASE, 50, 20.00);

        // Previous ledger exists
        $previousLedger = $this->createMockLedger(100, 10.00, 1000.00);

        $this->ledgerRepository
            ->shouldReceive('getLastLedgerBeforeDate')
            ->with($productId, $fromDate)
            ->once()
            ->andReturn($previousLedger);

        $this->ledgerRepository
            ->shouldReceive('deleteFromDateOnwards')
            ->with($productId, $fromDate)
            ->once();

        $this->transactionRepository
            ->shouldReceive('getFromDateOnwards')
            ->with($productId, $fromDate)
            ->once()
            ->andReturn(new Collection([$transaction]));

        // Should use previous ledger values as starting point
        $calculationResult = new LedgerCalculationResult(
            quantityOnHand: 150,
            averageCostPerUnit: 13.33,
            totalInventoryValue: 2000.00,
            costOfGoodsSold: null
        );

        $this->calculateWacService
            ->shouldReceive('calculate')
            ->with(TransactionConstants::TYPE_PURCHASE, 50, 20.00, 100, 1000.00, 10.00)
            ->once()
            ->andReturn($calculationResult);

        $this->ledgerRepository
            ->shouldReceive('create')
            ->once();

        $this->service->recalculateFromDate($productId, $fromDate);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function test_recalculate_processes_multiple_transactions_in_sequence(): void
    {
        $productId = 1;
        $fromDate = '2022-01-01';

        $transaction1 = $this->createMockTransaction(1, $productId, TransactionConstants::TYPE_PURCHASE, 100, 10.00);
        $transaction2 = $this->createMockTransaction(2, $productId, TransactionConstants::TYPE_SALE, 30, 15.00);

        $this->ledgerRepository
            ->shouldReceive('getLastLedgerBeforeDate')
            ->once()
            ->andReturn(null);

        $this->ledgerRepository
            ->shouldReceive('deleteFromDateOnwards')
            ->once();

        $this->transactionRepository
            ->shouldReceive('getFromDateOnwards')
            ->once()
            ->andReturn(new Collection([$transaction1, $transaction2]));

        // First transaction: purchase
        $result1 = new LedgerCalculationResult(100, 10.00, 1000.00, null);
        $this->calculateWacService
            ->shouldReceive('calculate')
            ->with(TransactionConstants::TYPE_PURCHASE, 100, 10.00, 0, 0, 0)
            ->once()
            ->andReturn($result1);

        // Second transaction: sale (should use result from first)
        $result2 = new LedgerCalculationResult(70, 10.00, 700.00, 300.00);
        $this->calculateWacService
            ->shouldReceive('calculate')
            ->with(TransactionConstants::TYPE_SALE, 30, 15.00, 100, 1000.00, 10.00)
            ->once()
            ->andReturn($result2);

        $this->ledgerRepository
            ->shouldReceive('create')
            ->twice();

        $this->service->recalculateFromDate($productId, $fromDate);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function test_recalculate_with_no_transactions_creates_no_ledger_entries(): void
    {
        $productId = 1;
        $fromDate = '2022-01-01';

        $this->ledgerRepository
            ->shouldReceive('getLastLedgerBeforeDate')
            ->once()
            ->andReturn(null);

        $this->ledgerRepository
            ->shouldReceive('deleteFromDateOnwards')
            ->once();

        $this->transactionRepository
            ->shouldReceive('getFromDateOnwards')
            ->once()
            ->andReturn(new Collection([]));

        // No calculate or create calls should be made
        $this->calculateWacService->shouldNotReceive('calculate');
        $this->ledgerRepository->shouldNotReceive('create');

        $this->service->recalculateFromDate($productId, $fromDate);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    private function createMockTransaction(int $id, int $productId, string $type, int $quantity, float $unitPrice): object
    {
        return (object) [
            'id' => $id,
            'product_id' => $productId,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ];
    }

    private function createMockLedger(int $quantityOnHand, float $averageCost, float $totalValue): InventoryLedger
    {
        $ledger = Mockery::mock(InventoryLedger::class)->makePartial();
        $ledger->shouldReceive('getAttribute')->with('quantity_on_hand')->andReturn($quantityOnHand);
        $ledger->shouldReceive('getAttribute')->with('average_cost_per_unit')->andReturn($averageCost);
        $ledger->shouldReceive('getAttribute')->with('total_inventory_value')->andReturn($totalValue);

        return $ledger;
    }
}
