<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employeeid',
        'firstname',
        'middlename',
        'lastname',
        'status',
        'mobile',
        'email',
        'birthdate',
        'sex',
        'address',
        'datehired',
        'dateseperated',
        'skill_id',
        'project_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employeeid)) {
                $latest = static::latest('id')->first();
                $nextId = $latest ? $latest->id + 1 : 1;
                $employee->employeeid = str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function projectHistories()
    {
        return $this->hasMany(EmployeeProjectHistory::class, 'employeeid', 'employeeid');
    }
}
