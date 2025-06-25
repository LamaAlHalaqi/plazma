<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
