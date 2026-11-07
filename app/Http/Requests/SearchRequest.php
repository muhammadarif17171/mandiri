<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return ['q' => 'nullable|string|max:150', 'status' => ['nullable', Rule::in(['aman', 'menipis', 'habis'])], 'page' => 'nullable|integer|min:1'];
    }
}
