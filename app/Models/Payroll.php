<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = [
        'employeeid',
        'periodid',
        'totalhours',
        'acquirehours',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    public function period()
    {
        return $this->belongsTo(DatePeriod::class, 'periodid', 'id');
    }
}
