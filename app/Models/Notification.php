<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    protected $table = 'notifications'; // اسم الجدول

    public $incrementing = false; // لأن المفتاح الأساسي UUID

    protected $keyType = 'string'; // UUID عبارة عن string

    protected $casts = [
        'data' => 'array',         // لتحويل حقل data إلى array تلقائياً
        'read_at' => 'datetime',   // تحويل read_at إلى كائن تاريخ
    ];
}
