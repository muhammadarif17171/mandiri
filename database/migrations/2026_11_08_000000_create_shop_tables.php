<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username', 50)->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'kasir']);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama', 150);
            $table->string('kategori', 80);
            $table->string('satuan', 30);
            $table->unsignedBigInteger('harga_jual');
            // Stok dan jumlah menggunakan integer; migrasikan keduanya jika perlu decimal.
            $table->unsignedBigInteger('stok')->default(0);
            $table->unsignedBigInteger('stok_minimum')->default(0);
            $table->timestamps();
        });
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('nama_sales', 100)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('no_faktur', 100);
            $table->date('tanggal')->index();
            $table->unsignedBigInteger('total');
            $table->timestamps();
            $table->unique(['distributor_id', 'no_faktur']);
        });
        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('jumlah');
            $table->unsignedBigInteger('harga_beli');
            // Sisa per faktur untuk menjamin faktur terpakai tidak dihapus (alokasi FIFO).
            $table->unsignedBigInteger('sisa');
            $table->timestamps();
            $table->unique(['stock_in_id', 'product_id']);
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('no_transaksi')->unique();
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('bayar');
            $table->unsignedBigInteger('kembalian');
            $table->timestamps();
            $table->index('created_at');
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('jumlah');
            $table->unsignedBigInteger('harga');
            // Menyimpan asal jumlah agar pembatalan mengembalikan sisa faktur yang tepat.
            $table->json('alokasi')->nullable();
            $table->timestamps();
        });
        Schema::create('sale_sequences', function (Blueprint $table) {
            $table->date('tanggal')->primary();
            $table->unsignedBigInteger('nomor')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['sale_sequences', 'sale_items', 'sales', 'stock_in_items', 'stock_ins', 'distributors', 'products', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
