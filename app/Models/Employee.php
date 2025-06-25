<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public function reservations()
    {
        return $this->belongsToMany(Reservation::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
