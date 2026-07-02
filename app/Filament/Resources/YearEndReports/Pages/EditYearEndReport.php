<?php

namespace App\Filament\Resources\YearEndReports\Pages;

use App\Filament\Resources\YearEndReports\YearEndReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditYearEndReport extends EditRecord
{
    protected static string $resource = YearEndReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
