<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'kasir'; }
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.jumlah' => 'required|integer|min:1|max:1000000',
            'bayar' => 'required|integer|min:0|max:1000000000000000',
        ];
    }
}
