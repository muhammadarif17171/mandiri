@extends('layouts.app')
@section('title', $product->exists ? 'Ubah produk' : 'Tambah produk')
@section('subtitle', 'Harga menggunakan rupiah. Stok bertambah melalui pencatatan barang masuk.')
@section('content')
<section class="panel form-panel">
    <form method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('owner.products.update', $product) : route('owner.products.store') }}">
        @csrf
        @if($product->exists)@method('put')@endif
        <div class="form-grid">
            @foreach(['kode' => 'Kode produk', 'nama' => 'Nama produk', 'kategori' => 'Kategori'] as $field => $label)
                <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $product->$field) }}" maxlength="{{ $field === 'kode' ? 40 : ($field === 'nama' ? 150 : 80) }}" required></label>
            @endforeach
            <label>Satuan
                <select name="satuan" required>
                    @foreach(['satuan', 'kg', 'gram', 'bungkus', 'gulung', 'botol', 'karung', 'kotak', 'kemasan 500 g', 'kemasan 800 g'] as $unit)
                        <option @selected(old('satuan', $product->satuan) === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </label>
            <label>Harga jual (Rp)<input name="harga_jual" type="number" min="0" max="100000000" step="1" value="{{ old('harga_jual', $product->harga_jual) }}" required></label>
            <label>Stok minimum<input name="stok_minimum" type="number" min="0" max="1000000" step="1" value="{{ old('stok_minimum', $product->stok_minimum ?? 0) }}" required></label>
            <label class="span-2">Foto produk (opsional)
                <input name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                <small class="muted">JPG, PNG, atau WebP; maksimal 2 MB.</small>
            </label>
            @if($product->foto_path)
                <div class="span-2 photo-edit">
                    <img class="product-photo-preview" src="{{ Storage::disk('public')->url($product->foto_path) }}" alt="Foto {{ $product->nama }}">
                    <label><input type="checkbox" name="hapus_foto" value="1" @checked(old('hapus_foto'))> Hapus foto saat menyimpan</label>
                </div>
            @endif
        </div>
        <div class="form-actions"><button class="btn">Simpan produk</button><a class="btn secondary" href="{{ route('owner.products.index') }}">Batal</a></div>
    </form>
</section>
@endsection
