<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model

{
    use HasFactory;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'employee_id',
        'start_time',
        'end_time',
        'status',
        'payment_method',
        'amount_paid',
        'points_used',
        'points_earned',
        'notes',
    ];




    public function user()
{
    return $this->belongsTo(User::class);
}


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
// app/Models/Reservation.php

    public function service()
    {
        return $this->belongsTo(Service::class);
    }



}
