@extends('layouts.app')
@section('title', 'Kasir')
@section('subtitle', 'Pilih produk, periksa keranjang, dan selesaikan pembayaran.')
@section('content')
<div class="pos-layout"><section class="panel"><div class="toolbar"><label class="search"><span class="sr-only">Cari produk</span><input id="product-search" placeholder="Cari nama, kode, atau kategori…" autocomplete="off"></label></div><div id="product-grid" class="product-grid"></div><p class="empty" id="product-empty" hidden>Tidak ada produk yang sesuai.</p></section>
<form class="panel cart" method="post" action="{{ route('kasir.store') }}" id="sale-form">@csrf<div class="panel-heading"><h2>Keranjang belanja</h2><span id="cart-count" class="muted">0 produk</span></div><div class="cart-lines" id="cart-lines"></div><div id="cart-inputs"></div><div class="cart-bottom"><div id="cart-error" class="notice error" role="alert" hidden></div><div class="total-row"><span>Total belanja</span><b id="cart-total">Rp0</b></div><label>Uang diterima (Rp)<input id="bayar" type="number" name="bayar" min="0" step="1" max="1000000000000000" required value="{{ old('bayar') }}" placeholder="0" inputmode="numeric"></label><div class="total-row change-row"><span>Kembalian</span><strong id="cart-change">Rp0</strong></div><button class="btn full" id="checkout" disabled>Bayar & cetak struk →</button></div></form></div>
<noscript><div class="notice error">Aktifkan JavaScript di peramban untuk menggunakan keranjang kasir.</div></noscript>
@endsection
@push('scripts')<script id="pos-data" type="application/json">@json(['products' => $products, 'items' => old('items', [])])</script>@endpush
