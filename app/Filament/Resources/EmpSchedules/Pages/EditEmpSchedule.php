<?php

namespace App\Filament\Resources\EmpSchedules\Pages;

use App\Filament\Resources\EmpSchedules\EmpScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmpSchedule extends EditRecord
{
    protected static string $resource = EmpScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
