<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(SearchRequest $request)
    {
        $q = $request->validated('q');
        $products = Product::when($request->filled('q'), fn ($query) => $query->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('kode', 'like', "%{$q}%")->orWhere('kategori', 'like', "%{$q}%")))->orderBy('nama')->paginate(15)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create() { return view('products.form', ['product' => new Product]); }
    public function edit(Product $product) { return view('products.form', compact('product')); }

    public function store(ProductRequest $request)
    {
        Product::create($request->validated());

        return redirect()->route('owner.products.index')->with('success', 'Produk ditambahkan. Catat barang masuk untuk menambah stok.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('owner.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                if ($product->stockInItems()->exists() || $product->saleItems()->exists()) {
                    throw ValidationException::withMessages(['produk' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat barang masuk atau penjualan.']);
                }
                $product->delete();
            });
        } catch (QueryException $e) {
            if (! str_starts_with((string) $e->getCode(), '23')) { throw $e; }
            throw ValidationException::withMessages(['produk' => 'Produk masih digunakan dalam riwayat transaksi.']);
        }

        return back()->with('success', 'Produk berhasil dihapus.');
    }
}
