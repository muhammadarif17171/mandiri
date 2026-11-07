<?php

use App\Http\Controllers\{AuthController, ProductController, DistributorController, StockAlertController};
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
    Route::prefix('owner')->name('owner.')->middleware('role:owner')->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
        Route::resource('produk', ProductController::class)->except('show')->parameters(['produk' => 'product'])->names('products');
        Route::resource('distributor', DistributorController::class)->except('show')->names('distributors');
    });
    Route::view('/kasir', 'sales.pos')->middleware('role:kasir')->name('kasir.pos');
});
