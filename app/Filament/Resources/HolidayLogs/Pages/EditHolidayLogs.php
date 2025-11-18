<?php

namespace App\Filament\Resources\HolidayLogs\Pages;

use App\Filament\Resources\HolidayLogs\HolidayLogsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHolidayLogs extends EditRecord
{
    protected static string $resource = HolidayLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
