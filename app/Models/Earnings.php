<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earnings extends Model
{
    use HasFactory;

    protected $table = 'earnings';

    protected $fillable = [
        'employeeid',
        'category_id',
        'amount',
        'status',
    ];

    /**
     * Relationships
     */

    // If you have an Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    // If you have a Category model
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}