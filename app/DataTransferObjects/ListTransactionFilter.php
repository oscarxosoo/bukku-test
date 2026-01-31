<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

class ListTransactionFilter
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?int $productId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            type: $request->query('type'),
            productId: $request->query('product_id') ? (int) $request->query('product_id') : null,
        );
    }
}
