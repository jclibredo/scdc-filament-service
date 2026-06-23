<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpSchedule extends Model
{
    use HasFactory;

    protected $table = 'emp_schedule';

    protected $fillable = [
        'employeeid',
        'timein',
        'timeout',
        'status',
        'workingHours',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function employData()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }
}
