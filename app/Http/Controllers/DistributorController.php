<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistributorRequest;
use App\Models\Distributor;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class DistributorController extends Controller
{
    public function index()
    {
        return view('distributors.index', ['distributors' => Distributor::withCount('stockIns')->orderBy('nama')->paginate(15)]);
    }
    public function create() { return view('distributors.form', ['distributor' => new Distributor]); }
    public function edit(Distributor $distributor) { return view('distributors.form', compact('distributor')); }
    public function store(DistributorRequest $request)
    {
        Distributor::create($request->validated());

        return redirect()->route('owner.distributors.index')->with('success', 'Distributor berhasil ditambahkan.');
    }
    public function update(DistributorRequest $request, Distributor $distributor)
    {
        $distributor->update($request->validated());

        return redirect()->route('owner.distributors.index')->with('success', 'Distributor berhasil diperbarui.');
    }
    public function destroy(Distributor $distributor)
    {
        if ($distributor->stockIns()->exists()) {
            throw ValidationException::withMessages(['distributor' => 'Distributor tidak dapat dihapus karena sudah memiliki faktur.']);
        }
        try { $distributor->delete(); }
        catch (QueryException $e) {
            if (! str_starts_with((string) $e->getCode(), '23')) { throw $e; }
            throw ValidationException::withMessages(['distributor' => 'Distributor masih digunakan pada faktur barang masuk.']);
        }

        return back()->with('success', 'Distributor berhasil dihapus.');
    }
}
