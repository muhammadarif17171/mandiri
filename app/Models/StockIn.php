<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    protected $fillable = ['distributor_id', 'user_id', 'no_faktur', 'tanggal', 'total'];
    protected function casts(): array { return ['tanggal' => 'date', 'total' => 'integer']; }
    public function distributor(): BelongsTo { return $this->belongsTo(Distributor::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(StockInItem::class); }
}
