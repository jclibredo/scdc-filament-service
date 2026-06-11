<?php

namespace App\Filament\Resources\DatePeriods\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDatePeriod extends EditRecord
{
    protected static string $resource = DatePeriodResource::class;
}
