<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Service extends Model
{
    use HasFactory;
 protected $appends = ['icon_url'];

    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return Storage::url('services/' . $this->icon);
        }

        return null;
    }

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
