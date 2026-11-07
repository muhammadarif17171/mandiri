<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    protected $fillable = ['product_id', 'jumlah', 'harga_beli', 'sisa'];
    protected function casts(): array { return ['jumlah' => 'integer', 'harga_beli' => 'integer', 'sisa' => 'integer']; }
    public function stockIn(): BelongsTo { return $this->belongsTo(StockIn::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
