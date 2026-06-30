<?php

namespace App\Filament\Resources\OtherDeductions\Pages;

use App\Filament\Resources\OtherDeductions\OtherDeductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOtherDeductions extends ListRecords
{
    protected static string $resource = OtherDeductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Deduction')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
