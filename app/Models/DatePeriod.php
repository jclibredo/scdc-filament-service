<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatePeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'employeetype',
        'category_id',
        'code',
        'datefrom',
        'dateto',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function employeeTypeCategory()
    {
        // Points employeetype (which holds the category ID) to the Category model
        return $this->belongsTo(Category::class, 'employeetype');
    }
}
