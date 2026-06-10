<?php

namespace App\Filament\Resources\DatePeriods\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDatePeriods extends ListRecords
{
    protected static string $resource = DatePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Period')
                ->color('warning')
                ->icon('heroicon-m-plus-circle'),

            Action::make('refresh')
                ->label('Load List')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(
                    fn() =>
                    session()->has('session_employeestatus') &&
                        session()->has('session_employeetype') &&
                        session()->has('session_periodcode')
                )
                ->action(function () {
                    session()->forget([
                        'session_employeetype',
                        'session_employeestatus',
                        'session_periodcode',
                    ]);
                    return redirect()->to(route('filament.admin.resources.date-periods.index'));
                }),

        ];
    }

    
}
