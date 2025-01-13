<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hutang extends Model
{
    protected $guarded = ['id'];

    public function hutangDetails()
    {
        return $this->hasMany(HutangDetail::class);
    }
}
