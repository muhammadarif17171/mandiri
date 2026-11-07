<?php

namespace App\Services;

use App\Models\{Sale, StockIn};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function sales(string $from, string $to): Builder
    {
        return Sale::where('created_at', '>=', Carbon::parse($from)->startOfDay())
            ->where('created_at', '<', Carbon::parse($to)->addDay()->startOfDay());
    }

    public function purchases(string $from, string $to): Builder
    {
        return StockIn::whereDate('tanggal', '>=', $from)->whereDate('tanggal', '<=', $to);
    }

    public function topProducts(string $from, string $to, int $limit = 5): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.created_at', '>=', Carbon::parse($from)->startOfDay())
            ->where('sales.created_at', '<', Carbon::parse($to)->addDay()->startOfDay())
            ->select('products.id', 'products.nama', 'products.satuan', 'products.kategori')
            ->selectRaw('SUM(sale_items.jumlah) as jumlah, SUM(sale_items.jumlah * sale_items.harga) as omzet')
            ->groupBy('products.id', 'products.nama', 'products.satuan', 'products.kategori')
            ->orderByDesc('jumlah')->orderBy('products.id')->limit($limit)->get();
    }

    public function charts(): array
    {
        $start = today()->startOfMonth()->subMonths(11);
        // Satu kueri agregat untuk seluruh grafik; hari dan bulan kosong diisi nol.
        $daily = $this->sales($start->toDateString(), today()->toDateString())
            ->selectRaw('DATE(created_at) as tanggal, SUM(total) as omzet')
            ->groupByRaw('DATE(created_at)')->orderBy('tanggal')->get()->pluck('omzet', 'tanggal');
        $days = collect(range(29, 0))->map(function ($offset) use ($daily) {
            $date = today()->subDays($offset);

            return ['label' => $date->format('d/m'), 'tanggal' => $date->toDateString(), 'total' => (int) $daily->get($date->toDateString(), 0)];
        });
        $monthly = $daily->groupBy(fn ($value, $date) => substr($date, 0, 7))->map(fn ($values) => $values->sum());
        $months = collect(range(11, 0))->map(function ($offset) use ($monthly) {
            $date = today()->startOfMonth()->subMonths($offset);

            return ['label' => $date->translatedFormat('M Y'), 'tanggal' => $date->format('Y-m'), 'total' => (int) $monthly->get($date->format('Y-m'), 0)];
        });

        return [$days, $months];
    }
}
