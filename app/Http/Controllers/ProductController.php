<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
        $attributes = $request->safe()->except(['foto', 'hapus_foto']);
        if ($request->hasFile('foto')) {
            $attributes['foto_path'] = $this->storePhoto($request->file('foto'));
        }
        Product::create($attributes);

        return redirect()->route('owner.products.index')->with('success', 'Produk ditambahkan. Catat barang masuk untuk menambah stok.');
    }

    public function update(ProductRequest $request, Product $product)
    {
        $attributes = $request->safe()->except(['foto', 'hapus_foto']);
        $oldPhotoPath = $product->foto_path;
        if ($request->hasFile('foto')) {
            $attributes['foto_path'] = $this->storePhoto($request->file('foto'));
        } elseif ($request->boolean('hapus_foto')) {
            $attributes['foto_path'] = null;
        }
        $product->update($attributes);
        if ($oldPhotoPath && $oldPhotoPath !== $product->foto_path) {
            $this->deletePhoto($oldPhotoPath);
        }

        return redirect()->route('owner.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $photoPath = $product->foto_path;
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
        $this->deletePhoto($photoPath);

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $path = Storage::disk('public')->putFile('products', $photo);
        if (! is_string($path)) {
            throw new RuntimeException('Foto produk gagal disimpan.');
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && ! Storage::disk('public')->delete($path)) {
            throw new RuntimeException('Foto produk lama gagal dihapus.');
        }
    }
}
