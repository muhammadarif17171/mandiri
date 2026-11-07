<?php

namespace Database\Seeders;

use App\Models\{Distributor, Product, StockIn, User};
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        if (StockIn::where('no_faktur', 'DEMO-AWAL')->exists()) {
            $this->command?->info('Data demo sudah tersedia. Tidak ditambahkan ulang.');
            return;
        }
        $clock = Carbon::getTestNow();
        $today = today();
        try {
            DB::transaction(function () use ($today) {
                $owner = User::where('username', 'owner')->firstOrFail();
                $cashier = User::where('username', 'kasir')->firstOrFail();
                $service = app(StockService::class);
                $catalog = [
                    ['DM-001', 'Pakan Ikan 781', 'Pakan ikan', 'kg', 15000, 15, 400],
                    ['DM-002', 'Pakan Burung Kenari', 'Pakan burung', 'bungkus', 12000, 10, 300],
                    ['DM-003', 'Pakan Kucing Tuna', 'Pakan kucing', 'kg', 28000, 12, 250],
                    ['DM-004', 'Benih Ikan Lele', 'Benih', 'satuan', 500, 100, 2000],
                    ['DM-005', 'Vitamin Ternak', 'Obat dan vitamin', 'botol', 35000, 8, 6],
                    ['DM-006', 'Obat Ikan', 'Obat dan vitamin', 'botol', 22000, 5, 3],
                    ['DM-007', 'Senar Pancing', 'Alat pancing', 'gulung', 18000, 10, 0],
                    ['DM-008', 'Kail Pancing', 'Alat pancing', 'bungkus', 8000, 10, 40],
                    ['DM-009', 'Pakan Kucing Kemasan', 'Pakan kucing', 'kemasan 500 g', 16000, 5, 30],
                ];
                $products = collect($catalog)->map(function ($row) {
                    return Product::firstOrCreate(['kode' => $row[0]], ['nama' => $row[1], 'kategori' => $row[2], 'satuan' => $row[3], 'harga_jual' => $row[4], 'stok_minimum' => $row[5]]);
                });
                $distributor = Distributor::firstOrCreate(['nama' => 'PT Pakan Nusantara (Demo)'], ['nama_sales' => 'Budi', 'no_hp' => '081234567890', 'alamat' => 'Padang, Sumatera Barat']);
                Carbon::setTestNow($today->copy()->startOfMonth()->subMonths(12)->setTime(8, 0));
                $items = [];
                foreach ($catalog as $index => $row) {
                    if ($row[6] > 0) {
                        $items[] = ['product_id' => $products[$index]->id, 'jumlah' => $row[6], 'harga_beli' => (int) round($row[4] * 0.75)];
                    }
                }
                $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'DEMO-AWAL', 'tanggal' => now()->toDateString(), 'items' => $items], $owner);
                $dates = collect(range(11, 1))->map(fn ($i) => $today->copy()->startOfMonth()->subMonths($i)->addDays(9))->merge(collect(range(29, 0))->map(fn ($i) => $today->copy()->subDays($i)));
                foreach ($dates as $index => $date) {
                    Carbon::setTestNow($date->copy()->setTime(9 + $index % 8, $index % 60));
                    $service->sell(['bayar' => 500000, 'items' => [
                        ['product_id' => $products[$index % 3]->id, 'jumlah' => 1 + $index % 4],
                        ['product_id' => $products[3]->id, 'jumlah' => 10 + $index % 12],
                    ]], $cashier);
                }
                Carbon::setTestNow($today->copy()->setTime(8, 0));
                $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'DEMO-RESTOCK', 'tanggal' => now()->toDateString(), 'items' => [['product_id' => $products[8]->id, 'jumlah' => 10, 'harga_beli' => 12000]]], $owner);
            });
        } finally {
            Carbon::setTestNow($clock);
        }
    }
}
