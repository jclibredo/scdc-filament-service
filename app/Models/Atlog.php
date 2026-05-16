<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee;

class Atlog extends Model
{
    use HasFactory;

    protected $table = 'attendance_logs';

    protected $fillable = [
        'user_id',
        'recorded_at',
        'status',
        'verification_mode',
        'work_code',
        'reserved'
    ];

    /**
     * Relation: Atlog belongs to an Employee
     */
   public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'employeeid');
    }
}
