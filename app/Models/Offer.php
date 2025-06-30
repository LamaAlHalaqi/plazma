<?php

namespace App\Models;
//use App\Models\Carbon;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model

{
      protected $fillable = [
        'service_id', 'title', 'description',
        'old_price', 'new_price',
        'start_date', 'end_date'
    ];

     protected $appends = ['status'];
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }


    
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }
     public function getStatusAttribute()
    {
        return Carbon::now()->between($this->start_date, $this->end_date) ? 'active' : 'expired';
    }


}
