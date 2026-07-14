<?php

namespace App\Filament\Resources\YearEndReports\Pages;

use App\Filament\Resources\YearEndReports\YearEndReportResource;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

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
                ->after(function ($record) {
                    // $record contains the newly created YearEndReport model instance
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Created a new year-end report code: {$record->code} (ID: {$record->id})",
                        'module'    => 'Year-End Reports',
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
                    session()->has('session_yearendreportspid')
                )
                ->action(function () {
                    session()->forget([
                        'session_yearendreportspid',
                    ]);
                    return redirect()->to(route('filament.admin.resources.year-end-reports.index'));
                }),
        ];
    }
}
