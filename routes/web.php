<?php

use App\Http\Controllers\{AuthController, ProductController, DistributorController, StockAlertController, StockInController, SaleController, DashboardController, ReportController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.process');
});
Route::middleware(['auth', 'role:owner,kasir'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->role === 'owner' ? 'owner.dashboard' : 'kasir.pos'))->name('dashboard');
    Route::get('/peringatan-stok', StockAlertController::class)->name('stock.alerts');
    Route::get('/struk/{sale}', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::prefix('owner')->name('owner.')->middleware('role:owner')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');
        Route::resource('produk', ProductController::class)->except('show')->parameters(['produk' => 'product'])->names('products');
        Route::resource('distributor', DistributorController::class)->except('show')->names('distributors');
        Route::resource('barang-masuk', StockInController::class)->only(['index', 'create', 'store', 'destroy'])->parameters(['barang-masuk' => 'stockIn'])->names('stock-ins');
        Route::delete('/penjualan/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });
    Route::get('/kasir', [SaleController::class, 'index'])->middleware('role:kasir')->name('kasir.pos');
    Route::post('/kasir', [SaleController::class, 'store'])->middleware('role:kasir')->name('kasir.store');
});
