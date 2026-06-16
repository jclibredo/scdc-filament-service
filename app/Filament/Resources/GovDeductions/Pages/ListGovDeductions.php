<?php

namespace App\Filament\Resources\GovDeductions\Pages;

use App\Filament\Resources\GovDeductions\GovDeductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGovDeductions extends ListRecords
{
    protected static string $resource = GovDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Deductions')
                ->color('warning')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
