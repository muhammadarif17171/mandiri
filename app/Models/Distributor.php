<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distributor extends Model
{
    protected $fillable = ['nama', 'nama_sales', 'no_hp', 'alamat'];

    public function stockIns(): HasMany { return $this->hasMany(StockIn::class); }
}
