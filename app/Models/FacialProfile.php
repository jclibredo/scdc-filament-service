<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacialProfile extends Model
{
    protected $fillable = ['employee_id', 'face_descriptor'];

    protected $casts = [
        'face_descriptor' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
