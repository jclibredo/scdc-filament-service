<?php

namespace App\Filament\Resources\ThirteenthMonths\Pages;

use App\Filament\Resources\ThirteenthMonths\ThirteenthMonthResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThirteenthMonths extends ListRecords
{
    protected static string $resource = ThirteenthMonthResource::class;
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Year End Reports')
                ->color('warning')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
