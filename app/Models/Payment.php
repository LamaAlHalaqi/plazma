<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'product_order_id',
        'payment_method',
        'amount',
        'receipt_path',
        'status',
    ];
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
    public function productorder()
    {
        return $this->belongsTo(ProductOrder::class);
    }
}
