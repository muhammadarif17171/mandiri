<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportService $reports)
    {
        $from = $request->validated('from'); $to = $request->validated('to');
        $query = $reports->sales($from, $to);
        $purchases = $reports->purchases($from, $to);

        return view('reports.index', [
            'from' => $from, 'to' => $to, 'total' => (clone $query)->sum('total'), 'count' => (clone $query)->count(),
            'purchaseTotal' => (clone $purchases)->sum('total'),
            'sales' => $query->with(['user', 'items.product'])->latest('id')->paginate(15)->withQueryString(),
            'purchases' => $purchases->with(['distributor', 'user', 'items.product'])->orderByDesc('tanggal')->orderByDesc('id')->paginate(10, ['*'], 'purchase_page')->withQueryString(),
            'top' => $reports->topProducts($from, $to, 10),
        ]);
    }

    public function export(ReportRequest $request, ReportService $reports)
    {
        $from = $request->validated('from'); $to = $request->validated('to');
        $type = $request->validated('jenis') ?? 'penjualan';

        return response()->streamDownload(function () use ($from, $to, $type, $reports) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            if ($type === 'pembelian') {
                fputcsv($out, ['Nomor faktur', 'Tanggal', 'Distributor', 'Pencatat', 'Total (Rp)'], ',', '"', '');
                foreach ($reports->purchases($from, $to)->with(['distributor', 'user'])->orderBy('id')->lazyById(500) as $invoice) {
                    fputcsv($out, [$this->safeCell($invoice->no_faktur), $invoice->tanggal->format('d/m/Y'), $this->safeCell($invoice->distributor->nama), $this->safeCell($invoice->user->name), $invoice->total], ',', '"', '');
                }
            } else {
                fputcsv($out, ['Nomor transaksi', 'Waktu', 'Kasir', 'Total (Rp)', 'Tunai (Rp)', 'Kembalian (Rp)'], ',', '"', '');
                foreach ($reports->sales($from, $to)->with('user')->orderBy('id')->lazyById(500) as $sale) {
                    fputcsv($out, [$sale->no_transaksi, $sale->created_at->format('d/m/Y H:i:s'), $this->safeCell($sale->user->name), $sale->total, $sale->bayar, $sale->kembalian], ',', '"', '');
                }
            }
            fclose($out);
        }, "{$type}-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCell(string $value): string
    {
        return preg_match('/^[\s]*[=+@\-\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
