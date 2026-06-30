<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Jobs\ProcessEmployeeCsv;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [


            Action::make('upload_employee')
                ->label('Upload Employees')
                ->icon('heroicon-o-arrow-up-tray')
                ->size('xs')
                ->color('success')
                ->outlined()
                ->button()
                ->form([
                    FileUpload::make('uploadfile')
                        ->label('Employee CSV File')
                        ->required()
                        ->acceptedFileTypes(['text/csv'])
                        ->disk('public')
                        ->directory('employees'),
                ])
                ->action(function (array $data) {
                    $file = $data['uploadfile'];
                    ProcessEmployeeCsv::dispatch($file);
                    Notification::make()
                        ->title('CSV Queued for Processing')
                        ->body('The CSV file will be processed shortly.')
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('New Employee')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
