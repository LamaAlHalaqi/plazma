<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function payments()
    {
        return $this->hasOne(Payment::class);
    }



    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

}
