<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function offers()
    {
        return $this->belongsToMany(Offer::class);
    }

    public function productorders()
    {
        return $this->belongsToMany(ProductOrder::class);
    }
}
