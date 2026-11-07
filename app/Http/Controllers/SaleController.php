<?php

namespace App\Http\Controllers;

use App\Http\Requests\{ReceiptRequest, SaleRequest};
use App\Models\{Product, Sale};
use App\Services\StockService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return view('sales.pos', ['products' => Product::orderBy('nama')->get(['id', 'kode', 'nama', 'kategori', 'satuan', 'harga_jual', 'stok'])]);
    }
    public function store(SaleRequest $request, StockService $service)
    {
        $sale = $service->sell($request->validated(), $request->user());

        return redirect()->route('sales.receipt', $sale);
    }
    public function receipt(ReceiptRequest $request, Sale $sale)
    {
        abort_unless($request->user()->role === 'owner' || $sale->user_id === $request->user()->id, 403);

        return view('sales.receipt', ['sale' => $sale->load(['items.product', 'user']), 'width' => $request->validated('width') ?? '80']);
    }
    public function destroy(Request $request, Sale $sale, StockService $service)
    {
        $service->cancel($sale, $request->user());

        return back()->with('success', 'Transaksi dibatalkan. Stok telah dikembalikan.');
    }
}
