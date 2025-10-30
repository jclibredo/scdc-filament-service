<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeProjectHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employeeid',
        'projectid',
        'employeetype',
        'employee_status',
        'datestarted',
        'dateended',
        'status',
    ];

    // Optional relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'projectid');
    }
}
