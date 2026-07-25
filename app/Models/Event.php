<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'details',
        'date',
        'image',
        'status',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'date' => 'date',
        'image' => 'array', // Crucial for handling multiple file paths
        'status' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Automatically delete images from storage when updating or deleting the record
        static::updating(function (Event $event) {
            if ($event->isDirty('image')) {
                $oldImages = $event->getOriginal('image') ?? [];
                $newImages = $event->image ?? [];
                $deletedImages = array_diff($oldImages, $newImages);

                foreach ($deletedImages as $file) {
                    Storage::disk('public')->delete($file);
                }
            }
        });

        static::deleting(function (Event $event) {
            if (is_array($event->image)) {
                foreach ($event->image as $file) {
                    Storage::disk('public')->delete($file);
                }
            }
        });
    }
}
