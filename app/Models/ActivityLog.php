<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'activity',
        'module',
        'ipaddress',
        'windows',
    ];
    protected static function booted(): void
    {
        static::created(function (ActivityLog $activityLog) {
            // 1. Fetch all SUPERADMIN users who get all system logs globally
            $admins = User::whereHas('userPermissions', function ($query) {
                $query->where('module', 'SUPERADMIN');
            })->get();

            // 2. Fetch the specific user who performed this action (if they aren't already an admin)
            $actionUser = null;
            if (filled($activityLog->user_id) && $activityLog->user_id !== 'System') {
                $actionUser = User::find($activityLog->user_id);
            }

            // 3. Merge them into a unique collection so no one gets a duplicate notification
            $recipients = collect($admins);

            if ($actionUser) {
                $recipients->push($actionUser);
            }

            $recipients = $recipients->unique('id');

            // 4. Send the notification directly to the target recipients' database bell icon
            foreach ($recipients as $recipient) {
                Notification::make()
                    ->title($activityLog->module ?? 'System Activity')
                    ->body($activityLog->activity)
                    ->info()
                    ->sendToDatabase($recipient);
            }
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // If needed, you can cast specific fields here (e.g., datetime formats)
    ];

    public function user(): BelongsTo
    {
        // Specifying 'user_id' as the foreign key explicitly since it's a string type
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
