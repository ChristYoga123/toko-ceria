<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HutangDetail extends Model
{
    protected $guarded = ['id'];

    public function hutang()
    {
        return $this->belongsTo(Hutang::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
}
