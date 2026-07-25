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
}
