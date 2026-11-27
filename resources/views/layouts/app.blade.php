<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sistem Informasi') · Bina Mandiri PS</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<aside class="sidebar">
    <a class="brand" href="{{ route('dashboard') }}"><span class="brand-icon">BM</span><span>Bina Mandiri <b>PS</b><small>Stok & penjualan</small></span></a>
    <p class="nav-label">RUANG KERJA {{ strtoupper(auth()->user()->role) }}</p>
    <nav aria-label="Menu utama">
    @if(auth()->user()->role === 'owner')
        <a @class(['active' => request()->routeIs('owner.dashboard')]) href="{{ route('owner.dashboard') }}"><span>▦</span> Dashboard</a>
        <a @class(['active' => request()->routeIs('owner.products.*')]) href="{{ route('owner.products.index') }}"><span>▤</span> Produk</a>
        <a @class(['active' => request()->routeIs('owner.distributors.*')]) href="{{ route('owner.distributors.index') }}"><span>♧</span> Distributor</a>
        @if(Route::has('owner.stock-ins.index'))<a @class(['active' => request()->routeIs('owner.stock-ins.*')]) href="{{ route('owner.stock-ins.index') }}"><span>↓</span> Barang masuk</a>@endif
        @if(Route::has('owner.reports.index'))<a @class(['active' => request()->routeIs('owner.reports.*')]) href="{{ route('owner.reports.index') }}"><span>▥</span> Laporan penjualan</a>@endif
    @else
        <a @class(['active' => request()->routeIs('kasir.pos', 'kasir.store')]) href="{{ route('kasir.pos') }}"><span>▣</span> Kasir</a>
        <a @class(['active' => request()->routeIs('kasir.history')]) href="{{ route('kasir.history') }}"><span>◷</span> Riwayat transaksi</a>
    @endif
        <a @class(['active' => request()->routeIs('stock.alerts')]) href="{{ route('stock.alerts') }}"><span>△</span> Peringatan stok</a>
    </nav>
    <div class="sidebar-footer"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></div><form method="post" action="{{ route('logout') }}">@csrf<button class="logout" title="Keluar" aria-label="Keluar">↗</button></form></div>
</aside>
<div class="workspace">
    <header class="topbar"><span>Sistem Informasi Pengelolaan Stok dan Analisis Penjualan</span><span class="today">{{ now()->translatedFormat('l, d F Y') }}</span></header>
    <main>
        <div class="page-heading"><div><p class="eyebrow">BINA MANDIRI PS · POULTRY SHOP</p><h1>@yield('title')</h1><p class="muted">@yield('subtitle')</p></div><div class="heading-actions">@yield('actions')</div></div>
        @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="notice error" role="alert"><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <footer class="app-footer">Bina Mandiri PS <span>Kelola stok dengan pasti, layani dengan sepenuh hati.</span></footer>
</div>
@stack('scripts')
</body></html>
