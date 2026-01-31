<?php

namespace App\DataTransferObjects;

class ListTransactionFilter
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?int $productId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
        );
    }
}
