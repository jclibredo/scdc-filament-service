<?php

namespace App\Filament\Resources\DatePeriods\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDatePeriods extends ListRecords
{
    protected static string $resource = DatePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
