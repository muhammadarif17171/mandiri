<?php

namespace Tests\Feature;

use App\Models\{Distributor, Product, Sale, StockIn, User};
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionTest extends TestCase
{
    use RefreshDatabase;
    protected User $owner;
    protected User $cashier;
    protected Product $product;
    protected Distributor $distributor;

    protected function setUp(): void
    {
        parent::setUp(); $this->seed();
        $this->owner = User::where('role', 'owner')->first();
        $this->cashier = User::where('role', 'kasir')->first();
        $this->product = Product::create(['kode' => 'P001', 'nama' => 'Pakan Ikan', 'kategori' => 'Pakan', 'satuan' => 'kg', 'harga_jual' => 15000, 'stok_minimum' => 3]);
        $this->distributor = Distributor::create(['nama' => 'PT Pakan']);
    }
    protected function incoming(string $number = 'INV-001', int $quantity = 10): array
    {
        return ['distributor_id' => $this->distributor->id, 'no_faktur' => $number, 'tanggal' => '2026-11-08', 'items' => [['product_id' => $this->product->id, 'jumlah' => $quantity, 'harga_beli' => 10000]]];
    }
    protected function stock(string $number = 'INV-001', int $quantity = 10): StockIn
    {
        return app(StockService::class)->receive($this->incoming($number, $quantity), $this->owner);
    }
    protected function order(int $quantity = 2): array
    {
        return ['bayar' => 50000, 'items' => [['product_id' => $this->product->id, 'jumlah' => $quantity]]];
    }

    public function test_receiving_adds_stock_and_duplicate_invoice_is_rejected(): void
    {
        $this->actingAs($this->owner)->post('/owner/barang-masuk', $this->incoming())->assertSessionHasNoErrors();
        $this->assertSame(10, $this->product->fresh()->stok);
        $this->assertDatabaseHas('stock_ins', ['total' => 100000]);
        $this->assertSame('2026-11-08', StockIn::firstOrFail()->tanggal->toDateString());
        $this->post('/owner/barang-masuk', $this->incoming())->assertSessionHasErrors('no_faktur');
        $this->assertDatabaseCount('stock_ins', 1);
        $this->get('/owner/barang-masuk')->assertOk()->assertSee('INV-001');
    }

    public function test_sale_uses_server_price_decrements_stock_and_prints_both_sizes(): void
    {
        $this->stock();
        $data = $this->order(); $data['items'][0]['harga'] = 1; $data['total'] = 1;
        $response = $this->actingAs($this->cashier)->post('/kasir', $data)->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();
        $response->assertRedirect('/struk/'.$sale->id);
        $this->assertSame(8, $this->product->fresh()->stok);
        $this->assertSame(30000, $sale->total);
        $this->assertSame(20000, $sale->kembalian);
        $this->assertMatchesRegularExpression('/^TRX-\d{8}-0001$/', $sale->no_transaksi);
        $this->get('/struk/'.$sale->id.'?width=58')->assertOk()->assertSee('narrow')->assertSee('Rp30.000');
        $this->get('/struk/'.$sale->id.'?width=80')->assertOk()->assertSee('Rp20.000');
        $this->get('/struk/'.$sale->id.'?width=99')->assertSessionHasErrors('width');
    }

    public function test_insufficient_stock_and_payment_roll_back_all_changes(): void
    {
        $this->stock();
        $this->actingAs($this->cashier)->post('/kasir', $this->order(11))->assertSessionHasErrors('items');
        $data = $this->order(); $data['bayar'] = 1;
        $this->post('/kasir', $data)->assertSessionHasErrors('bayar');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $this->product->fresh()->stok);
        $this->assertDatabaseHas('stock_in_items', ['sisa' => 10]);
        $this->assertDatabaseCount('sale_sequences', 0);
    }

    public function test_zero_negative_fractional_and_duplicate_lines_are_rejected(): void
    {
        $this->stock(); $this->actingAs($this->cashier);
        foreach ([0, -1, 1.5] as $quantity) {
            $data = $this->order(); $data['items'][0]['jumlah'] = $quantity;
            $this->post('/kasir', $data)->assertSessionHasErrors('items.0.jumlah');
        }
        $data = $this->order(); $data['items'][] = $data['items'][0];
        $this->post('/kasir', $data)->assertSessionHasErrors('items.0.product_id');
        $this->assertSame(10, $this->product->fresh()->stok);
    }

    public function test_empty_stock_product_cannot_be_sold(): void
    {
        $this->actingAs($this->cashier)->post('/kasir', $this->order())->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_owner_cancels_sale_and_restores_original_invoice_stock_once(): void
    {
        $this->stock();
        $sale = app(StockService::class)->sell($this->order(), $this->cashier);
        $this->actingAs($this->cashier)->delete('/owner/penjualan/'.$sale->id)->assertForbidden();
        $this->actingAs($this->owner)->delete('/owner/penjualan/'.$sale->id)->assertSessionHasNoErrors();
        $this->assertSame(10, $this->product->fresh()->stok);
        $this->assertDatabaseHas('stock_in_items', ['sisa' => 10]);
        $this->assertDatabaseCount('sales', 0); $this->assertDatabaseCount('sale_items', 0);
        $this->delete('/owner/penjualan/'.$sale->id)->assertNotFound();
        $next = app(StockService::class)->sell($this->order(), $this->cashier);
        $this->assertStringEndsWith('-0002', $next->no_transaksi);
    }

    public function test_used_invoice_cannot_be_deleted_even_after_new_deliveries(): void
    {
        $invoice = $this->stock();
        app(StockService::class)->sell($this->order(), $this->cashier);
        $this->stock('INV-002', 20);
        $this->actingAs($this->owner)->delete('/owner/barang-masuk/'.$invoice->id)->assertSessionHasErrors('faktur');
        $this->assertSame(28, $this->product->fresh()->stok);
        $this->assertDatabaseCount('stock_ins', 2);
    }

    public function test_unused_invoice_deletion_reverses_stock_and_cascades_items(): void
    {
        $invoice = $this->stock();
        $this->actingAs($this->owner)->delete('/owner/barang-masuk/'.$invoice->id)->assertSessionHasNoErrors();
        $this->assertSame(0, $this->product->fresh()->stok);
        $this->assertDatabaseCount('stock_in_items', 0);
        $this->assertDatabaseCount('stock_ins', 0);
    }

    public function test_master_deletion_with_history_is_rejected(): void
    {
        $this->stock();
        $this->actingAs($this->owner)->delete('/owner/produk/'.$this->product->id)->assertSessionHasErrors('produk');
        $this->delete('/owner/distributor/'.$this->distributor->id)->assertSessionHasErrors('distributor');
        $this->assertDatabaseCount('products', 1); $this->assertDatabaseCount('distributors', 1);
    }

    public function test_cashier_cannot_manage_incoming_and_owner_cannot_sell(): void
    {
        $this->actingAs($this->cashier);
        foreach (['/owner/barang-masuk', '/owner/barang-masuk/create'] as $path) { $this->get($path)->assertForbidden(); }
        $this->post('/owner/barang-masuk', $this->incoming())->assertForbidden();
        $this->actingAs($this->owner)->post('/kasir', $this->order())->assertForbidden();
        $this->get('/owner/barang-masuk/create')->assertOk();
    }

    public function test_numbers_start_at_one_on_each_day_and_receipt_access_is_restricted(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 23:59:59', 'Asia/Jakarta'));
        $this->stock();
        $first = app(StockService::class)->sell($this->order(), $this->cashier);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-09 00:00:00', 'Asia/Jakarta'));
        $second = app(StockService::class)->sell($this->order(), $this->cashier);
        $this->assertSame('TRX-20261108-0001', $first->no_transaksi);
        $this->assertSame('TRX-20261109-0001', $second->no_transaksi);
        $other = User::create(['name' => 'Kasir Lain', 'username' => 'lain', 'password' => 'password123', 'role' => 'kasir']);
        $this->actingAs($other)->get('/struk/'.$first->id)->assertForbidden();
        $this->actingAs($this->owner)->get('/struk/'.$first->id)->assertOk();
        $this->travelBack();
    }
}
