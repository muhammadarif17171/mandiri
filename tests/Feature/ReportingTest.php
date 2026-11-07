<?php

namespace Tests\Feature;

use App\Models\{Distributor, Product, User};
use App\Services\{ReportService, StockService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_charts_period_boundaries_reports_and_csv(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 15:00:00', 'Asia/Jakarta'));
        $this->seed(); $owner = User::where('role', 'owner')->first(); $cashier = User::where('role', 'kasir')->first();
        $product = Product::create(['kode' => 'P1', 'nama' => 'Pakan Ikan', 'kategori' => 'Pakan', 'satuan' => 'kg', 'harga_jual' => 15000, 'stok_minimum' => 9]);
        $distributor = Distributor::create(['nama' => '=SUM(1+1)']);
        $service = app(StockService::class);
        $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'INV-NOV', 'tanggal' => '2026-11-08', 'items' => [['product_id' => $product->id, 'jumlah' => 10, 'harga_beli' => 10000]]], $owner);
        $order = ['bayar' => 50000, 'items' => [['product_id' => $product->id, 'jumlah' => 1]]];
        $todaySale = $service->sell($order, $cashier);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-31 23:59:59', 'Asia/Jakarta'));
        $pastSale = $service->sell($order, $cashier);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 15:00:00', 'Asia/Jakarta'));
        [$days, $months] = app(ReportService::class)->charts();
        $this->assertCount(30, $days); $this->assertCount(12, $months);
        $this->assertSame(15000, $days->last()['total']);
        $this->assertSame(15000, $months->last()['total']);
        $this->assertSame(15000, $months[10]['total']);
        $this->assertSame(0, $months->first()['total']);
        $this->actingAs($owner)->get('/owner/dashboard')->assertOk()->assertViewHas('revenue', 15000)->assertViewHas('count', 1)->assertViewHas('restockCount', 1)->assertSee('Pakan Ikan');
        $this->get('/owner/laporan')->assertOk()->assertSee($todaySale->no_transaksi)->assertDontSee($pastSale->no_transaksi)->assertSee('INV-NOV')->assertViewHas('total', 15000);
        $this->get('/owner/laporan?from=2026-10-31&to=2026-10-31')->assertOk()->assertSee($pastSale->no_transaksi)->assertDontSee($todaySale->no_transaksi)->assertDontSee('INV-NOV');
        $csv = $this->get('/owner/laporan/export')->assertOk()->streamedContent();
        $this->assertStringContainsString($todaySale->no_transaksi, $csv);
        $this->assertStringNotContainsString($pastSale->no_transaksi, $csv);
        $csv = $this->get('/owner/laporan/export?jenis=pembelian')->assertOk()->streamedContent();
        $this->assertStringContainsString('INV-NOV', $csv); $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $service->cancel($todaySale, $owner);
        $this->get('/owner/laporan')->assertOk()->assertViewHas('total', 0)->assertDontSee($todaySale->no_transaksi);
        $this->get('/owner/dashboard')->assertOk()->assertViewHas('revenue', 0)->assertViewHas('count', 0);
        $this->travelBack();
    }

    public function test_date_validation_and_owner_only_reports(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'owner')->first());
        $this->get('/owner/laporan?from=2026-11-08&to=2026-11-01')->assertSessionHasErrors('to');
        $this->get('/owner/laporan?from=abc')->assertSessionHasErrors('from');
        $this->get('/owner/laporan/export?jenis=invalid')->assertSessionHasErrors('jenis');
        $this->actingAs(User::where('role', 'kasir')->first());
        $this->get('/owner/laporan')->assertForbidden();
        $this->get('/owner/laporan/export')->assertForbidden();
    }
}
