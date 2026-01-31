<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'transaction_type' => ['required', Rule::in([
                TransactionConstants::TYPE_PURCHASE,
                TransactionConstants::TYPE_SALE,
            ])],
            'transaction_date' => [
                'required',
                'date',
                Rule::unique('transactions')
                    ->where('product_id', $this->product_id),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
