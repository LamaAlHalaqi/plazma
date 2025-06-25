<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
    public function productorder()
    {
        return $this->belongsTo(ProductOrder::class);
    }
}
