<?php

return [
    'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
    'integer' => ':attribute harus berupa bilangan bulat.', 'numeric' => ':attribute harus berupa angka.',
    'array' => ':attribute harus berupa daftar.', 'in' => 'Pilihan :attribute tidak valid.',
    'exists' => ':attribute tidak ditemukan.', 'unique' => ':attribute sudah digunakan.',
    'distinct' => 'Produk yang sama tidak boleh ditambahkan dua kali.',
    'date' => ':attribute harus berupa tanggal yang valid.', 'date_format' => 'Format :attribute harus :format.',
    'after_or_equal' => ':attribute harus sama dengan atau setelah :date.',
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.', 'array' => ':attribute maksimal :max baris.'],
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.', 'array' => ':attribute minimal :min baris.'],
    'attributes' => ['username' => 'Nama pengguna', 'password' => 'Kata sandi', 'kode' => 'Kode produk', 'nama' => 'Nama', 'kategori' => 'Kategori', 'satuan' => 'Satuan', 'harga_jual' => 'Harga jual', 'stok_minimum' => 'Stok minimum', 'nama_sales' => 'Nama sales', 'no_hp' => 'Nomor HP', 'alamat' => 'Alamat', 'q' => 'Pencarian', 'status' => 'Status', 'page' => 'Halaman', 'items' => 'Daftar barang', 'items.*.product_id' => 'Produk', 'items.*.jumlah' => 'Jumlah', 'items.*.harga_beli' => 'Harga beli', 'distributor_id' => 'Distributor', 'no_faktur' => 'Nomor faktur', 'tanggal' => 'Tanggal', 'bayar' => 'Uang diterima', 'from' => 'Tanggal mulai', 'to' => 'Tanggal akhir', 'width' => 'Lebar struk'],
];
