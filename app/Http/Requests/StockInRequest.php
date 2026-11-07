<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockInRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'owner'; }
    public function rules(): array
    {
        return [
            'distributor_id' => 'required|integer|exists:distributors,id',
            'no_faktur' => ['required', 'string', 'max:100', Rule::unique('stock_ins', 'no_faktur')->where('distributor_id', $this->input('distributor_id'))],
            'tanggal' => 'required|date_format:Y-m-d',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.jumlah' => 'required|integer|min:1|max:1000000',
            'items.*.harga_beli' => 'required|integer|min:0|max:100000000',
        ];
    }
}
