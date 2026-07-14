<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\ActivityLog;
use App\Notifications\AccountActivationNotification;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New User')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function ($record) {
                    // This gives you the created User model instance
                    $user = $record;
                    // Trigger your notification
                    $user->notify(new AccountActivationNotification($user));
                    // 2. Log the activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System', // The admin/user who created this account
                        'activity'  => "Created a new user account: {$user->email} (ID: {$user->id})",
                        'module'    => 'User Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(), // Captures browser/OS environment info
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
