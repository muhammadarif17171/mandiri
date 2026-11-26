<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'owner'; }
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:40', Rule::unique('products', 'kode')->ignore($this->route('product'))],
            'nama' => 'required|string|max:150', 'kategori' => 'required|string|max:80',
            'satuan' => ['required', Rule::in(['satuan', 'kg', 'gram', 'bungkus', 'gulung', 'botol', 'karung', 'kemasan 500 g', 'kemasan 800 g'])],
            'harga_jual' => 'required|integer|min:0|max:100000000',
            'stok_minimum' => 'required|integer|min:0|max:1000000',
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['sometimes', 'boolean'],
        ];
    }
}
