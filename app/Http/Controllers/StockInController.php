<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Models\{Distributor, Product, StockIn};
use App\Services\StockService;
use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function index()
    {
        $invoices = StockIn::with(['distributor', 'user', 'items.product'])->latest('id')->paginate(10);

        return view('stock-ins.index', compact('invoices'));
    }
    public function create()
    {
        return view('stock-ins.create', ['products' => Product::orderBy('nama')->get(), 'distributors' => Distributor::orderBy('nama')->get()]);
    }
    public function store(StockInRequest $request, StockService $service)
    {
        $service->receive($request->validated(), $request->user());

        return redirect()->route('owner.stock-ins.index')->with('success', 'Faktur tersimpan. Stok produk telah bertambah.');
    }
    public function destroy(Request $request, StockIn $stockIn, StockService $service)
    {
        $service->removeInvoice($stockIn, $request->user());

        return back()->with('success', 'Faktur dihapus dan stok dikurangi kembali.');
    }
}
