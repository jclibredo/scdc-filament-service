<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Notifications\AccountActivationNotification;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
