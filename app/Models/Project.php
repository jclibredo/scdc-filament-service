<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_code',
        'name',
        'address',
        'status',
        'image',
        'datecovered',
        'scope',
    ];

    protected $casts = [ // Crucial for handling multiple file paths
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        // 1. Delete old file when image is replaced or removed during an update
        static::updating(function (Project $project) {
            if ($project->isDirty('image') && $project->getOriginal('image')) {
                Storage::disk('public')->delete($project->getOriginal('image'));
            }
        });

        // 2. Delete file when the entire project record is deleted
        static::deleting(function (Project $project) {
            if ($project->image) {
                Storage::disk('public')->delete($project->image);
            }
        });
    }
    // protected static function booted(): void
    // {
    //     static::updating(function (Project $project) {
    //         $original = $project->getOriginal('image');

    //         // ONLY delete if an old image existed AND it is different from the new image
    //         if ($project->isDirty('image') && $original && $original !== $project->image) {
    //             Storage::disk('public')->delete($original);
    //         }
    //     });

    //     static::deleting(function (Project $project) {
    //         if ($project->image) {
    //             Storage::disk('public')->delete($project->image);
    //         }
    //     });
    // }
}
