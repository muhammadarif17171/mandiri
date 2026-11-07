<?php

// Jalankan hanya dengan database MySQL pengujian yang namanya berakhir _verification.
use App\Models\{Distributor, Product, Sale, User};
use App\Services\StockService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || ! str_ends_with(config('database.connections.mysql.database'), '_verification')) {
    fwrite(STDERR, "Gunakan database MySQL khusus dengan nama berakhiran _verification.\n");
    exit(1);
}
$service = app(StockService::class);
$cashier = User::where('role', 'kasir')->firstOrFail();

if (($argv[1] ?? '') === 'worker') {
    try {
        $sale = $service->sell(['bayar' => 1000000, 'items' => [['product_id' => (int) $argv[2], 'jumlah' => (int) $argv[3]]]], $cashier);
        echo json_encode(['id' => $sale->id, 'nomor' => $sale->no_transaksi])."\n";
        exit(0);
    } catch (ValidationException $e) {
        echo json_encode(['ditolak' => $e->errors()])."\n";
        exit(2);
    }
}

$owner = User::where('role', 'owner')->firstOrFail();
$suffix = bin2hex(random_bytes(5));
$distributor = Distributor::create(['nama' => 'Pemasok Uji '.$suffix]);
$product = Product::create(['kode' => 'QA-'.$suffix, 'nama' => 'Produk Uji Konkurensi', 'kategori' => 'Pengujian', 'satuan' => 'satuan', 'harga_jual' => 1000, 'stok_minimum' => 0]);
$invoice = $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'QA-'.$suffix, 'tanggal' => today()->toDateString(), 'items' => [['product_id' => $product->id, 'jumlah' => 5, 'harga_beli' => 500]]], $owner);
$processes = [];
try {
    foreach ([4, 4] as $quantity) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', (string) $product->id, (string) $quantity], dirname(__DIR__), null, null, 30);
        $process->start(); $processes[] = $process;
    }
    $codes = [];
    foreach ($processes as $process) {
        $codes[] = $process->wait();
        echo $process->getOutput();
        if ($process->getErrorOutput()) { fwrite(STDERR, $process->getErrorOutput()); }
    }
    sort($codes);
    if ($codes !== [0, 2] || $product->fresh()->stok !== 1 || $product->saleItems()->count() !== 1) {
        throw new RuntimeException('Persaingan stok gagal: '.json_encode($codes));
    }
    echo "LULUS: dari dua penjualan bersamaan (masing-masing 4 dari stok 5), hanya satu berhasil; stok akhir 1.\n";
    $extra = $service->receive(['distributor_id' => $distributor->id, 'no_faktur' => 'QA-TAMBAH-'.$suffix, 'tanggal' => today()->toDateString(), 'items' => [['product_id' => $product->id, 'jumlah' => 10, 'harga_beli' => 500]]], $owner);
    $processes = [];
    foreach (range(1, 4) as $index) {
        $process = new Process([PHP_BINARY, __FILE__, 'worker', (string) $product->id, '1'], dirname(__DIR__), null, null, 30);
        $process->start(); $processes[] = $process;
    }
    $numbers = [];
    foreach ($processes as $process) {
        if ($process->wait() !== 0) { throw new RuntimeException($process->getOutput().$process->getErrorOutput()); }
        $numbers[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['nomor'];
    }
    if (count(array_unique($numbers)) !== 4 || $product->fresh()->stok !== 7) {
        throw new RuntimeException('Penomoran bersamaan tidak konsisten.');
    }
    echo "LULUS: empat transaksi bersamaan mendapat nomor unik dan stok akhir benar.\n";
} finally {
    foreach ($processes as $process) { if ($process->isRunning()) { $process->stop(); } }
    $sales = Sale::whereHas('items', fn ($query) => $query->where('product_id', $product->id))->get();
    foreach ($sales as $sale) { $service->cancel($sale, $owner); }
    $service->removeInvoice($invoice, $owner);
    if (isset($extra)) { $service->removeInvoice($extra, $owner); }
    $product->delete(); $distributor->delete();
    echo "Data uji persaingan telah dibersihkan.\n";
}
