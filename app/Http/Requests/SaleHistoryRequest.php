<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleHistoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'kasir'; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: today()->startOfMonth()->toDateString(),
            'to' => $this->input('to') ?: today()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
