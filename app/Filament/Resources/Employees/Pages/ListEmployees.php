<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeProjectHistory;
use App\Models\Project;
use App\Models\Skill;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Support\Enums\Size;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Action::make('importEmployeesCsvFormat')
                ->label('Import .CSV File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(Size::ExtraSmall)
                ->outlined()
                ->form([
                    FileUpload::make('dat_file')
                        ->label('Select Employee CSV File')
                        ->required()
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/comma-separated-values',
                        ])
                        ->maxSize(10240) // 10 MB
                        ->helperText('Upload a CSV file matching the structure from the Export Template action.'),
                ])
                ->action(function (array $data) {
                    // 1. Resolve storage file path
                    $filePath = Storage::disk('public')->path($data['dat_file']);

                    if (! file_exists($filePath)) {
                        $filePath = storage_path('app/public/' . $data['dat_file']);
                    }

                    if (! file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found!')
                            ->danger()
                            ->send();

                        return;
                    }

                    // 2. Open and parse CSV file
                    $file = fopen($filePath, 'r');
                    $header = fgetcsv($file);

                    if (! $header) {
                        Notification::make()
                            ->title('Invalid CSV File')
                            ->body('The uploaded CSV file is empty or corrupt.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Sanitize header row (strip UTF-8 BOM, spaces, lowercase)
                    $header = array_map(function ($h) {
                        $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);

                        return strtolower(trim($h));
                    }, $header);

                    $importedCount = 0;

                    while (($row = fgetcsv($file)) !== false) {
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        $rowData = array_combine($header, $row);

                        $employeeId = Str::upper(trim($rowData['employeeid'] ?? ''));
                        $firstName  = Str::upper(trim($rowData['firstname'] ?? ''));
                        $lastName   = Str::upper(trim($rowData['lastname'] ?? ''));

                        // Core fields check
                        if (empty($employeeId) || empty($firstName) || empty($lastName)) {
                            continue;
                        }

                        // 3. Resolve Category IDs dynamically
                        $empStatusVal = trim($rowData['empstatus'] ?? '');
                        $empStatusId  = is_numeric($empStatusVal) ? $empStatusVal : Category::where('cat', 'EMPLOYEE_STATUS')
                            ->where('name', 'LIKE', $empStatusVal)
                            ->value('id');

                        $empTypeVal = trim($rowData['employeetype'] ?? '');
                        $empTypeId  = is_numeric($empTypeVal) ? $empTypeVal : Category::where('cat', 'EMPLOYEE_TYPE')
                            ->where('name', 'LIKE', $empTypeVal)
                            ->value('id');

                        $partnerVal = trim($rowData['partners'] ?? '');
                        $partnerId  = is_numeric($partnerVal) ? $partnerVal : Category::where('cat', 'SUBCON')
                            ->where('name', 'LIKE', $partnerVal)
                            ->value('id');

                        // 4. Resolve Skill ID dynamically
                        $skillVal = trim($rowData['skill'] ?? '');
                        $skillId  = is_numeric($skillVal) ? $skillVal : Skill::where('title', 'LIKE', $skillVal)->value('id');

                        // 5. Resolve Project ID dynamically
                        $projectVal = trim($rowData['project'] ?? '');
                        $projectId  = is_numeric($projectVal) ? $projectVal : Project::where('name', 'LIKE', $projectVal)->value('id');

                        // 6. Format Dates safely (YYYY-MM-DD or null)
                        $birthDate     = ! empty(trim($rowData['birthdate'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['birthdate']))) : null;
                        $dateHired     = ! empty(trim($rowData['datehired'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['datehired']))) : null;
                        $dateSeperated = ! empty(trim($rowData['dateseperated'] ?? '')) ? date('Y-m-d', strtotime(trim($rowData['dateseperated']))) : null;

                        // 7. Upsert record by employeeid
                        Employee::updateOrCreate(
                            ['employeeid' => $employeeId],
                            [
                                'firstname'     => $firstName,
                                'middlename'    => Str::upper(trim($rowData['middlename'] ?? '')),
                                'lastname'      => $lastName,
                                'status'        => filter_var($rowData['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
                                'mobile'        => trim($rowData['mobile'] ?? ''),
                                'empstatus'     => $empStatusId,
                                'email'         => strtolower(trim($rowData['email'] ?? '')),
                                'birthdate'     => $birthDate,
                                'sex'           => ucfirst(strtolower(trim($rowData['sex'] ?? 'Male'))),
                                'address'       => Str::upper(trim($rowData['address'] ?? '')),
                                'datehired'     => $dateHired,
                                'dateseperated' => $dateSeperated,
                                'employeetype'  => $empTypeId,
                                'partners'      => $partnerId,
                                'skill_id'      => $skillId,
                                'project_id'    => $projectId,
                            ]
                        );

                        $importedCount++;
                    }

                    fclose($file);

                    // Remove temporary uploaded file
                    Storage::disk('public')->delete($data['dat_file']);

                    // Log System Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Imported/Updated {$importedCount} employees from CSV",
                        'module'    => 'Employee Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('CSV Import Successful')
                        ->body("Successfully processed {$importedCount} employee records.")
                        ->success()
                        ->send();
                }),
            Action::make('exportEmployeesCsvFormat')
                ->label('Export CSV Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->size('xs')
                ->outlined()
                ->url(route('employees.export.csv'))
                ->openUrlInNewTab(),

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
