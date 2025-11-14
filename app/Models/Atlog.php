<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atlog extends Model
{
    use HasFactory;

    protected $table = 'atlogs';

    protected $fillable = [
        'employeeid',
        'date',
        'time_in',
        'break_out',
        'break_in',
        'time_out',
    ];

    /**
     * Relation: Atlog belongs to an Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    public function scopeWithEmployee($query)
    {
        return $query->select('atlogs.*')
            ->join('employees', 'atlogs.employeeid', '=', 'employees.employeeid');
    }
}