<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earnings extends Model
{
    use HasFactory;

    protected $table = 'earnings';

    protected $fillable = [
        'employee_id',
        'title',
        'amount',
        'status',
    ];

    /**
     * Relationships
     */

    // If you have an Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employeeid');
    }

    // public function category()
    // {
    //     return $this->belongsTo(Category::class, 'title', 'id');
    // }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
