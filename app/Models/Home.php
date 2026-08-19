<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Home extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'smallimage',
        'bigimage',
    ];

    /**
     * The attributes that should be cast.
     * Automatically casts JSON array from database into a PHP array (and vice versa)
     * for handling multiple image paths (perfect for Filament FileUpload multiple mode).
     *
     * @var array<string, string>
     */
    protected $casts = [
        'smallimage' => 'array',
    ];
}
