<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    private const PRODUCTS = [
        ['5 11', 'Pakan Ayam', 14000, 'kg'],
        ['5 12', 'Pakan Ayam', 15000, 'kg'],
        ['5 94', 'Pakan Ayam', 13000, 'kg'],
        ['3 11', 'Pakan Ayam', 19000, 'kg'],
        ['5 91', 'Pakan Ayam', 15000, 'kg'],
        ['BP 12', 'Pakan Ayam', 12000, 'kg'],
        ['5 24 TA', 'Pakan Ayam', 10000, 'kg'],
        ['N582', 'Pakan Ayam', 10000, 'kg'],
        ['124', 'Pakan Ayam', 12000, 'kg'],
        ['Milet Putih', 'Pakan Burung', 17000, 'kg'],
        ['Milet Merah', 'Pakan Burung', 18000, 'kg'],
        ['Milet Kuning', 'Pakan Burung', 15000, 'kg'],
        ['781 -1', 'Pakan Ikan / Lele (781)', 17000, 'kg'],
        ['781 -2', 'Pakan Ikan / Lele (781)', 16000, 'kg'],
        ['781 -3', 'Pakan Ikan / Lele (781)', 16000, 'kg'],
        ['Alveet -2', 'Pakan Ikan / Nila (Alveet)', 12000, 'kg'],
        ['Alveet -3', 'Pakan Ikan / Nila (Alveet)', 11000, 'kg'],
        ['Alveet -4', 'Pakan Ikan / Nila (Alveet)', 10000, 'kg'],
        ['PF1000', 'Pakan Ikan / Pakan Ikan Lainnya', 23000, 'kg'],
        ['PF99', 'Pakan Ikan / Pakan Ikan Lainnya', 24000, 'kg'],
        ['PF800', 'Pakan Ikan / Pakan Ikan Lainnya', 25000, 'kg'],
        ['PF500', 'Pakan Ikan / Pakan Ikan Lainnya', 25000, 'kg'],
        ['TP80', 'Pakan Ikan / Pakan Ikan Lainnya', 25000, 'kg'],
        ['TP50', 'Pakan Ikan / Pakan Ikan Lainnya', 25000, 'kg'],
        ['Lezato', 'Pakan Kucing', 23000, 'kg'],
        ['Bolt', 'Pakan Kucing', 21000, 'kemasan 800 g'],
        ['Cat Choice', 'Pakan Kucing', 21000, 'kemasan 800 g'],
        ['Execl', 'Pakan Kucing', 13000, 'kemasan 500 g'],
        ['Carbon', 'Peralatan Pancing / Mata Kail', 10000, 'bungkus'],
        ['Charm ST', 'Peralatan Pancing / Mata Kail', 7000, 'bungkus'],
        ['Hugo', 'Peralatan Pancing / Mata Kail', 5000, 'bungkus'],
        ['Dhaicy', 'Peralatan Pancing / Mata Kail', 12000, 'bungkus'],
        ['Pancing Kotak', 'Peralatan Pancing / Mata Kail', 12000, 'kotak'],
        ['King Power / Classic', 'Peralatan Pancing / Benang Layangan', 38000, 'gulung'],
        ['Extraline', 'Peralatan Pancing / Benang Layangan', 12000, 'gulung'],
        ['Penyu', 'Peralatan Pancing / Benang Layangan', 20000, 'gulung'],
        ['Iron', 'Peralatan Pancing / Benang Layangan', 10000, 'gulung'],
        ['NPK', 'Pupuk', 24000, 'kg'],
        ['Urea', 'Pupuk', 15000, 'kg'],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTS as $index => [$name, $category, $price, $unit]) {
            Product::firstOrCreate(
                ['kode' => sprintf('BM-%03d', $index + 1)],
                [
                    'nama' => $name,
                    'kategori' => $category,
                    'satuan' => $unit,
                    'harga_jual' => $price,
                    'stok_minimum' => 0,
                ],
            );
        }
    }
}
