<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }



    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

}
