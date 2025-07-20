<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'email',
        'phone',
        'work_start_time',
        'work_end_time',
    ];
    public function reservations()
    {
        return $this->belongsToMany(Reservation::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
