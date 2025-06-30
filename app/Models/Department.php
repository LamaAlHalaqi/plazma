<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Department extends Model
{
    use HasFactory;
       // لإضافة الخاصية الجديدة تلقائيًا في JSON
    protected $appends = ['icon_url'];

    // Accessor للحصول على مسار الصورة
    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return Storage::url('departments/' . $this->icon);
        }

        return null;
    }

    protected $fillable =['name','icon'];
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

}
