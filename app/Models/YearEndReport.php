<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearEndReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'emptype',
        'empstatus',
        'partners',
        'projectid',
        'status',
        'datefrom',
        'dateto',
        'rep_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'datefrom' => 'date',
        'dateto' => 'date',
    ];

    public function otherdeductionData()
    {
        return $this->hasMany(OtherDeductionLog::class, 'date_period_id', 'yearendrepid');
    }

    public function govdeductionData()
    {
        return $this->hasMany(GovDeductionLog::class, 'date_period_id', 'yearendrepid');
    }

    public function adjustmentData()
    {
        return $this->hasMany(Adjustment::class, 'date_period_id', 'yearendrepid');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'empstatus');
    }

    public function employeeTypeCategory()
    {
        // Points employeetype (which holds the category ID) to the Category model
        return $this->belongsTo(Category::class, 'emptype');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'projectid', 'project_code');
    }
}
