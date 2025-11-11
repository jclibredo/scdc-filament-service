<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atlog extends Model
{
    use HasFactory;

    protected $fillable = [
        'userid',
        'timein',
        'timeout',
        'datetime',
    ];

    // Optional relationship to User model
    public function user()
    {
        return $this->belongsTo(Employee::class, 'userid');
    }
}
