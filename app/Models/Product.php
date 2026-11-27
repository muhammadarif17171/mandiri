<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['kode', 'nama', 'kategori', 'satuan', 'harga_jual', 'stok_minimum', 'foto_path'];

    protected function casts(): array
    {
        return ['harga_jual' => 'integer', 'stok' => 'integer', 'stok_minimum' => 'integer'];
    }

    public function getStatusAttribute(): string
    {
        return $this->stok === 0 ? 'habis' : ($this->stok <= $this->stok_minimum ? 'menipis' : 'aman');
    }

    public function stockInItems(): HasMany { return $this->hasMany(StockInItem::class); }
    public function saleItems(): HasMany { return $this->hasMany(SaleItem::class); }
}
