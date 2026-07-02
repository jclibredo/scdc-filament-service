<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirteenthMonth extends Model
{
    use HasFactory;

    protected $table = 'thirteenth_months';

    protected $fillable = [
        'periodid',
        'employeeid',
        'earnings', // renamed from total_amount
        // 'emptype',
        // 'empstatus',
        'partners',
        'yearendrepid',
        'project',
        'allowance',
        'datestart',
        'dateend',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allowance' => 'decimal:2',
        'earnings' => 'decimal:2',
        'datestart' => 'date',
        'dateend' => 'date',
    ];
    /**
     * Relation: ThirteenthMonth belongs to an Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    /**
     * Relation: ThirteenthMonth belongs to a Period
     * (Assuming you have a Period model and table)
     */
    public function period()
    {
        return $this->belongsTo(DatePeriod::class, 'periodid', 'id');
    }
}
