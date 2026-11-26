<?php

namespace App\Http\Controllers;

use App\Http\Requests\{ReceiptRequest, SaleRequest};
use App\Models\{Product, Sale};
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SaleController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('nama')->get(['id', 'kode', 'nama', 'kategori', 'satuan', 'harga_jual', 'stok', 'foto_path'])
            ->map(function (Product $product) {
                $product->foto_url = $product->foto_path ? Storage::disk('public')->url($product->foto_path) : null;

                return $product;
            });

        return view('sales.pos', compact('products'));
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
