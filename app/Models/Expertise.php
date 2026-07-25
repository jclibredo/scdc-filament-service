<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Expertise extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Optional explicit table definition if Laravel pluralization varies.
     */
    protected $table = 'expertises';

    protected $fillable = [
        'title',
        'details',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Delete old image when replaced or removed during an update
        static::updating(function (Expertise $expertise) {
            if ($expertise->isDirty('image') && $expertise->getOriginal('image')) {
                Storage::disk('public')->delete($expertise->getOriginal('image'));
            }
        });

        // Delete image when the expertise record is deleted
        static::deleting(function (Expertise $expertise) {
            if ($expertise->image) {
                Storage::disk('public')->delete($expertise->image);
            }
        });
    }
}
