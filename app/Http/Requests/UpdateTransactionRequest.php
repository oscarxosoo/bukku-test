<?php

namespace App\Http\Requests;

use App\Constants\TransactionConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transactionId = $this->route('transaction')?->id;

        return [
            'transaction_type' => ['sometimes', Rule::in([
                TransactionConstants::TYPE_PURCHASE,
                TransactionConstants::TYPE_SALE,
            ])],
            'transaction_date' => [
                'sometimes',
                'date',
                Rule::unique('transactions')
                    ->where('product_id', $this->route('transaction')?->product_id)
                    ->ignore($transactionId),
            ],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
