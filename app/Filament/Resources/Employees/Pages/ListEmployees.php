<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Jobs\ProcessEmployeeCsv;
use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

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
                ->after(function (Employee $record) {
                    EmployeeProjectHistory::create([
                        'employeeid'      => $record->employeeid,
                        'projectid'       => $record->project_id,
                        'employeetype'    => $record->employeetype,
                        'employee_status' => $record->empstatus,
                        'datestarted'     => $record->datehired,
                        'dateended'       => $record->dateseperated,
                        'status'          => $record->dateseperated === null ? true : false,
                    ]);

                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Registered new employee profile: {$record->lastname}, {$record->firstname} {$record->middlename} (Assigned ID: {$record->employeeid})",
                        'module'    => 'Employee Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
