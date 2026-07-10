<?php

namespace App\Models;

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
