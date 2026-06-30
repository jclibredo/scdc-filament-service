<?php

namespace App\Filament\Resources\HolidayLogs\Pages;

use App\Filament\Resources\HolidayLogs\HolidayLogsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHolidayLogs extends ListRecords
{
    protected static string $resource = HolidayLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Holidays Logs')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
