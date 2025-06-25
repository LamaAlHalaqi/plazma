<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable =[
        'department_id','name','description','price','points','duration','icon'
    ];


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
}
