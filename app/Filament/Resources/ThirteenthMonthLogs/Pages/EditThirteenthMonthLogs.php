<?php

namespace App\Filament\Resources\ThirteenthMonthLogs\Pages;

use App\Filament\Resources\ThirteenthMonthLogs\ThirteenthMonthLogsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThirteenthMonthLogs extends EditRecord
{
    protected static string $resource = ThirteenthMonthLogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
