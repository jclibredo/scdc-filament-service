<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Userlog extends Model
{
    protected $table = 'userlogs';
    protected $fillable = [
        'created_by',
        'module',
        'module_id',
        'action',
        'details',
    ];
    protected $casts = [
        'created_by' => 'integer',
        // If details contains JSON strings, you can change this to 'array'
        'details'    => 'string',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
