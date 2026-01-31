<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in([
                TransactionConstants::TYPE_PURCHASE,
                TransactionConstants::TYPE_SALE,
            ])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
