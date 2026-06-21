<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cat',    // 🟢 Added
        'status', // 🟢 Added
        'frequency',
    ];

    protected $casts = [
        'status' => 'boolean', // 🟢 Forces 1/0 from DB to be true/false in PHP
    ];

    public function SubConEmployee()
    {
        return $this->hasMany(Employee::class, 'partners', 'id');
    }
}
