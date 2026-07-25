<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category',
        'image',
        'name',
        'details',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Delete old image when replaced or removed during an update
        static::updating(function (Asset $asset) {
            if ($asset->isDirty('image') && $asset->getOriginal('image')) {
                Storage::disk('public')->delete($asset->getOriginal('image'));
            }
        });

        // Delete image when the asset record is deleted
        static::deleting(function (Asset $asset) {
            if ($asset->image) {
                Storage::disk('public')->delete($asset->image);
            }
        });
    }
}
