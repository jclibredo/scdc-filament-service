<?php

namespace App\Filament\Resources\Atlogs\Pages;

use App\Filament\Resources\Atlogs\AtlogResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAtlogs extends ListRecords
{
    protected static string $resource = AtlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('importBiometrics')
                ->label('Import Biometrics')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->action(function () {
                    // TODO: your import logic here.
                    // Example: (You can replace this)
                    // ImportBiometricsAtlogs::dispatch();

                    Notification::make()
                        ->title('Biometrics Import Started')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Import Biometrics Logs')
                ->modalSubheading('This will import biometric logs into the Atlogs table.'),
        ];
    }
}
