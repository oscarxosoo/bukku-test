<?php

namespace App\DataTransferObjects;

class UpdateTransactionData
{
    public function __construct(
        public readonly ?string $transactionType = null,
        public readonly ?string $transactionDate = null,
        public readonly ?int $quantity = null,
        public readonly ?float $unitPrice = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionType: $data['transaction_type'] ?? null,
            transactionDate: $data['transaction_date'] ?? null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
            unitPrice: isset($data['unit_price']) ? (float) $data['unit_price'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'transaction_type' => $this->transactionType,
            'transaction_date' => $this->transactionDate,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
        ];
    }
}
