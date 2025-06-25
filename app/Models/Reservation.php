<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function points()
    {
        return $this->hasOne(Point::class);
    }


}
