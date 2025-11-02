<?php

namespace App\Filament\Resources\DatePeriods\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDatePeriod extends CreateRecord
{
    protected static string $resource = DatePeriodResource::class;
    // protected function mutateFormDataBeforeCreate(array $data): array
    // {

    //     dd($data);
    // }
}
