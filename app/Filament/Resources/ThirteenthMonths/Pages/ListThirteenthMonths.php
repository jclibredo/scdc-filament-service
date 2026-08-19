<?php

namespace App\Filament\Resources\ThirteenthMonths\Pages;

use App\Filament\Resources\ThirteenthMonths\ThirteenthMonthResource;
use App\Filament\Resources\YearEndReports\YearEndReportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListThirteenthMonths extends ListRecords
{
    protected static string $resource = ThirteenthMonthResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_billing')
                ->label('Back')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-arrow-left')
                ->action(function () {
                    $code = session('session_yearendreportspid');
                    session()->forget([
                        'session_yearendreportspid',
                        'session_partnersid',
                        'session_employeetypeid',
                        'session_employeestatusid',
                        'session_projectid'
                    ]);
                    session([
                        'session_yearendreportspid' => $code,
                    ]);
                    return redirect()->to(YearEndReportResource::getUrl('index'));
                }),
        ];
    }
}
