<?php

namespace App\Filament\Resources\DatePeriods\Pages;

use App\Filament\Resources\DatePeriods\DatePeriodResource;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListDatePeriods extends ListRecords
{
    protected static string $resource = DatePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Period')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->after(function ($record) {
                    // 5. Log New Date Period Creation
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Configured a new processing Date Period: Code [{$record->code}] ({$record->datefrom} to {$record->dateto}) | Baseline OT Rate: {$record->overtime_rate}%",
                        'module'    => 'Report Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),

            Action::make('refresh')
                ->label('Load List')
                ->icon('heroicon-o-arrow-path')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
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
                        'session_partners',
                        'session_project',
                    ]);
                    return redirect()->to(route('filament.admin.resources.date-periods.index'));
                }),

        ];
    }
}
