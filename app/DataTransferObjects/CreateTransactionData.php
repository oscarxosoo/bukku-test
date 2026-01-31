<?php

namespace App\DataTransferObjects;

class CreateTransactionData
{
    public function __construct(
        public readonly int $productId,
        public readonly string $transactionType,
        public readonly string $transactionDate,
        public readonly int $quantity,
        public readonly float $unitPrice,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            transactionType: $data['transaction_type'],
            transactionDate: $data['transaction_date'],
            quantity: $data['quantity'],
            unitPrice: $data['unit_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'transaction_type' => $this->transactionType,
            'transaction_date' => $this->transactionDate,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
        ];
    }
}
