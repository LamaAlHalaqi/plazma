<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable =[
        'department_id','name','description','price','points','points_cost','duration','icon'
    ];
// accessor لإرجاع رابط الصورة
    public function getIconUrlAttribute()
    {
        return $this->icon ? asset('storage/services/' . $this->icon) : null;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function favoredByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }


    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

}

