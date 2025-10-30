<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'date_started',
        'date_ended',
        'amount',
        'status',
    ];

    protected $casts = [
        'date_started' => 'date',
        'date_ended' => 'date',
        'status' => 'boolean',
    ];
}
