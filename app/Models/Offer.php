<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discount_percentage',
        'start_datetime',
        'end_datetime',
        'description',
        'points',
        'image',

    ];





    public function products()
    {
        return $this->belongsToMany(Product::class);
    }



    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

}
