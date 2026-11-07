<?php

namespace Tests\Feature;

use App\Models\{Distributor, Product, Sale, User};
use App\Services\{ReportService, StockService};
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifo_across_invoices_and_cancel_releases_exact_batches(): void
    {
        $this->seed();
        $owner = User::where('role', 'owner')->first(); $cashier = User::where('role', 'kasir')->first();
        $product = Product::create(['kode' => 'P1', 'nama' => 'Pakan', 'kategori' => 'Pakan', 'satuan' => 'kg', 'harga_jual' => 1000, 'stok_minimum' => 1]);
        $distributor = Distributor::create(['nama' => 'Pemasok']); $service = app(StockService::class);
        $invoices = [];
        foreach ([3, 5] as $index => $quantity) {
            $invoices[] = $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'INV-'.$index, 'tanggal' => today()->toDateString(), 'items' => [['product_id' => $product->id, 'jumlah' => $quantity, 'harga_beli' => 500]]], $owner);
        }
        $sale = $service->sell(['bayar' => 10000, 'items' => [['product_id' => $product->id, 'jumlah' => 4]]], $cashier);
        $this->assertSame(0, $invoices[0]->items()->first()->sisa);
        $this->assertSame(4, $invoices[1]->items()->first()->sisa);
        $service->cancel($sale, $owner);
        $this->assertSame(3, $invoices[0]->items()->first()->sisa);
        $this->assertSame(5, $invoices[1]->items()->first()->sisa);
        $service->removeInvoice($invoices[0], $owner);
        $this->assertSame(5, $product->fresh()->stok);
    }

    public function test_late_failure_rolls_back_sale_items_stock_and_sequence(): void
    {
        $this->seed();
        $owner = User::where('role', 'owner')->first(); $cashier = User::where('role', 'kasir')->first();
        $service = app(StockService::class); $distributor = Distributor::create(['nama' => 'Pemasok']);
        $products = collect(['A', 'B'])->map(fn ($code) => Product::create(['kode' => $code, 'nama' => $code, 'kategori' => 'Pakan', 'satuan' => 'kg', 'harga_jual' => 1000, 'stok_minimum' => 1]));
        $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'INV', 'tanggal' => today()->toDateString(), 'items' => $products->map(fn ($p) => ['product_id' => $p->id, 'jumlah' => 5, 'harga_beli' => 500])->all()], $owner);
        // Simulasi gangguan di database setelah baris pertama telah ditulis.
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: App\\Models\\SaleItem', function ($item) use ($products) {
            if ($item->product_id === $products[1]->id) { throw new \RuntimeException('Simulasi kegagalan'); }
        });
        try {
            $service->sell(['bayar' => 10000, 'items' => $products->map(fn ($p) => ['product_id' => $p->id, 'jumlah' => 2])->all()], $cashier);
            $this->fail('Transaksi seharusnya gagal.');
        } catch (\RuntimeException $e) { $this->assertSame('Simulasi kegagalan', $e->getMessage()); }
        $this->assertDatabaseCount('sales', 0); $this->assertDatabaseCount('sale_items', 0); $this->assertDatabaseCount('sale_sequences', 0);
        foreach ($products as $product) { $this->assertSame(5, $product->fresh()->stok); }
        $this->assertSame(10, (int) \App\Models\StockInItem::sum('sisa'));
    }

    public function test_demo_is_explicit_repeatable_and_pages_render_with_populated_data(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 16:00:00', 'Asia/Jakarta'));
        $this->seed(DemoSeeder::class); $count = Sale::count();
        $this->assertSame(41, $count); $this->assertDatabaseCount('users', 2);
        $this->seed(DemoSeeder::class); $this->assertSame($count, Sale::count());
        $this->actingAs(User::where('role', 'owner')->first());
        foreach (['/owner/dashboard', '/owner/laporan', '/owner/produk', '/owner/distributor', '/owner/barang-masuk', '/peringatan-stok', '/owner/produk/'.Product::firstOrFail()->id.'/edit', '/owner/distributor/'.Distributor::firstOrFail()->id.'/edit'] as $path) { $this->get($path)->assertOk(); }
        $this->actingAs(User::where('role', 'kasir')->first())->get('/kasir')->assertOk();
        $this->travelBack();
    }

    public function test_month_end_charts_and_daily_numbers_reset(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-03-31 12:00:00', 'Asia/Jakarta'));
        [$days, $months] = app(ReportService::class)->charts();
        $this->assertSame('2025-04', $months->first()['tanggal']);
        $this->assertSame('2026-03', $months->last()['tanggal']);
        $this->assertCount(30, $days);
        $this->travelBack();
    }

    public function test_invalid_login_and_throttling_are_localized(): void
    {
        $this->seed();
        for ($i = 0; $i < 5; $i++) { $this->post('/login', ['username' => 'owner', 'password' => 'wrong'])->assertSessionHasErrors('username'); }
        $this->post('/login', ['username' => 'owner', 'password' => 'wrong'])->assertStatus(429)->assertSee('Terlalu banyak percobaan');
        $this->assertGuest();
    }
}
