<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Product;

class StockAlertController extends Controller
{
    public function __invoke(SearchRequest $request)
    {
        $status = $request->validated('status');
        $q = $request->validated('q');
        $products = Product::query()
            ->when($q, fn ($w) => $w->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%")))
            ->when(! $status, fn ($w) => $w->whereColumn('stok', '<=', 'stok_minimum'))
            ->when($status === 'aman', fn ($w) => $w->whereColumn('stok', '>', 'stok_minimum'))
            ->when($status === 'menipis', fn ($w) => $w->where('stok', '>', 0)->whereColumn('stok', '<=', 'stok_minimum'))
            ->when($status === 'habis', fn ($w) => $w->where('stok', 0))
            ->orderBy('stok')->orderBy('nama')->paginate(20)->withQueryString();

        return view('stock.alerts', compact('products'));
    }
}
