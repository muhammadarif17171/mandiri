<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistributorRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'owner'; }
    public function rules(): array
    {
        return ['nama' => 'required|string|max:150', 'nama_sales' => 'nullable|string|max:100', 'no_hp' => 'nullable|string|max:30', 'alamat' => 'nullable|string|max:1000'];
    }
}
