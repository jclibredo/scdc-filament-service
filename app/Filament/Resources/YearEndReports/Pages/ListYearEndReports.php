<?php

namespace App\Filament\Resources\YearEndReports\Pages;

use App\Filament\Resources\YearEndReports\YearEndReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYearEndReports extends ListRecords
{
    protected static string $resource = YearEndReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Year-End Report')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
