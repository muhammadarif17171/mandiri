<?php

namespace App\Services;

use App\Models\{Product, Sale, StockIn, StockInItem, User};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function receive(array $data, User $user): StockIn
    {
        abort_unless($user->role === 'owner', 403);
        try {
            return DB::transaction(function () use ($data, $user) {
                $products = $this->lockProducts(collect($data['items'])->pluck('product_id')->all());
                $total = 0;
                foreach ($data['items'] as $item) {
                    $total += (int) $item['jumlah'] * (int) $item['harga_beli'];
                }
                $invoice = StockIn::create([
                    'distributor_id' => $data['distributor_id'], 'user_id' => $user->id,
                    'no_faktur' => $data['no_faktur'], 'tanggal' => $data['tanggal'], 'total' => $total,
                ]);
                foreach ($data['items'] as $item) {
                    $quantity = (int) $item['jumlah'];
                    $product = $products->get($item['product_id']);
                    $invoice->items()->create(['product_id' => $product->id, 'jumlah' => $quantity, 'harga_beli' => (int) $item['harga_beli'], 'sisa' => $quantity]);
                    $product->stok += $quantity;
                    $product->save();
                }

                return $invoice;
            }, 3);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['no_faktur' => 'Nomor faktur distributor ini sudah dicatat.']);
        }
    }

    public function sell(array $data, User $user): Sale
    {
        abort_unless($user->role === 'kasir', 403);

        return DB::transaction(function () use ($data, $user) {
            // Baris urutan dikunci sebelum produk, sehingga transaksi bersamaan tidak memakai nomor sama.
            $date = today()->toDateString();
            DB::table('sale_sequences')->insertOrIgnore(['tanggal' => $date, 'nomor' => 0]);
            $sequence = DB::table('sale_sequences')->where('tanggal', $date)->lockForUpdate()->first();
            $number = $sequence->nomor + 1;
            DB::table('sale_sequences')->where('tanggal', $date)->update(['nomor' => $number]);

            $products = $this->lockProducts(collect($data['items'])->pluck('product_id')->all());
            $total = 0;
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if ($product->stok < (int) $item['jumlah']) {
                    throw ValidationException::withMessages(['items' => "Stok {$product->nama} tidak cukup. Tersedia {$product->stok} {$product->satuan}."]);
                }
                $total += $product->harga_jual * (int) $item['jumlah'];
            }
            if ((int) $data['bayar'] < $total) {
                throw ValidationException::withMessages(['bayar' => 'Uang diterima kurang dari total belanja.']);
            }
            $sale = Sale::create([
                'user_id' => $user->id, 'no_transaksi' => 'TRX-'.now()->format('Ymd').'-'.sprintf('%04d', $number),
                'total' => $total, 'bayar' => (int) $data['bayar'], 'kembalian' => (int) $data['bayar'] - $total,
            ]);
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['jumlah'];
                $remaining = $quantity;
                $allocation = [];
                // FIFO berdasarkan urutan penerimaan, bukan tanggal faktur yang dapat diisi mundur.
                $batches = StockInItem::where('product_id', $product->id)->where('sisa', '>', 0)->orderBy('id')->lockForUpdate()->get();
                foreach ($batches as $batch) {
                    $taken = min($remaining, $batch->sisa);
                    $batch->sisa -= $taken;
                    $batch->save();
                    $allocation[] = ['stock_in_item_id' => $batch->id, 'jumlah' => $taken];
                    $remaining -= $taken;
                    if ($remaining === 0) { break; }
                }
                if ($remaining !== 0) {
                    throw ValidationException::withMessages(['items' => "Riwayat stok {$product->nama} tidak konsisten. Periksa barang masuk."]);
                }
                $sale->items()->create(['product_id' => $product->id, 'jumlah' => $quantity, 'harga' => $product->harga_jual, 'alokasi' => $allocation]);
                $product->stok -= $quantity;
                $product->save();
            }

            return $sale;
        }, 3);
    }

    public function cancel(Sale $sale, User $user): void
    {
        abort_unless($user->role === 'owner', 403);
        DB::transaction(function () use ($sale) {
            // Semua alur memakai urutan lock produk yang sama untuk menghindari deadlock.
            $products = $this->lockProducts($sale->items()->pluck('product_id')->all());
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            foreach ($sale->items()->get() as $item) {
                foreach ($item->alokasi as $allocation) {
                    $batch = StockInItem::whereKey($allocation['stock_in_item_id'])->lockForUpdate()->firstOrFail();
                    $batch->sisa += $allocation['jumlah'];
                    $batch->save();
                }
                $product = $products->get($item->product_id);
                $product->stok += $item->jumlah;
                $product->save();
            }
            $sale->delete(); // Item dihapus oleh foreign key cascade.
        }, 3);
    }

    public function removeInvoice(StockIn $invoice, User $user): void
    {
        abort_unless($user->role === 'owner', 403);
        DB::transaction(function () use ($invoice) {
            $products = $this->lockProducts($invoice->items()->pluck('product_id')->all());
            $invoice = StockIn::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $items = $invoice->items()->lockForUpdate()->get();
            foreach ($items as $item) {
                if ($item->sisa !== $item->jumlah || $products->get($item->product_id)->stok < $item->jumlah) {
                    throw ValidationException::withMessages(['faktur' => 'Faktur tidak dapat dihapus karena stok dari faktur ini sudah terpakai. Batalkan penjualan terkait terlebih dahulu.']);
                }
            }
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $product->stok -= $item->jumlah;
                $product->save();
            }
            $invoice->delete();
        }, 3);
    }

    private function lockProducts(array $ids): Collection
    {
        $ids = array_unique($ids);
        $products = Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($products->count() !== count($ids)) {
            throw ValidationException::withMessages(['items' => 'Salah satu produk tidak lagi tersedia. Muat ulang halaman dan periksa keranjang.']);
        }

        return $products;
    }
}
