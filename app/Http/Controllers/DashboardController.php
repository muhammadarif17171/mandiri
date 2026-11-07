<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports)
    {
        [$days, $months] = $reports->charts();
        $todaySales = $reports->sales(today()->toDateString(), today()->toDateString());

        return view('dashboard', [
            'days' => $days, 'months' => $months, 'revenue' => (clone $todaySales)->sum('total'),
            'count' => $todaySales->count(), 'productCount' => Product::count(),
            'restockCount' => Product::whereColumn('stok', '<=', 'stok_minimum')->count(),
            'restock' => Product::whereColumn('stok', '<=', 'stok_minimum')->orderBy('stok')->orderBy('nama')->limit(8)->get(),
            'top' => $reports->topProducts(today()->subDays(29)->toDateString(), today()->toDateString()),
        ]);
    }
}
