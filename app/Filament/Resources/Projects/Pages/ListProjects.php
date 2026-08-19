<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ActivityLog;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Support\Enums\Size;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importProjectsCsvFormat')
                ->label('Import .CSV File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(Size::ExtraSmall)
                ->outlined()
                ->form([
                    FileUpload::make('dat_file')
                        ->label('Select CSV File')
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
                        ->helperText('Please upload a .CSV file containing "project_code", "name", "datecovered", "scope", and "address" headers.'),
                ])
                ->action(function (array $data) {
                    // 1. Resolve uploaded file path
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

                    // 2. Open and read CSV
                    $file = fopen($filePath, 'r');
                    $header = fgetcsv($file);

                    if (! $header) {
                        Notification::make()
                            ->title('Invalid CSV File')
                            ->body('The uploaded file is empty or corrupted.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Sanitize headers (remove UTF-8 BOM and trim spaces)
                    $header = array_map(function ($h) {
                        $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);

                        return strtolower(trim($h));
                    }, $header);

                    $importedCount = 0;

                    while (($row = fgetcsv($file)) !== false) {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        $rowData = array_combine($header, $row);

                        $projectCode = Str::upper(trim($rowData['project_code'] ?? ''));
                        $name        = Str::upper(trim($rowData['name'] ?? ''));
                        $datecovered = Str::upper(trim($rowData['datecovered'] ?? ''));
                        $scope       = Str::upper(trim($rowData['scope'] ?? ''));
                        $address     = Str::upper(trim($rowData['address'] ?? ''));

                        // Name is required to proceed
                        if (! empty($name)) {
                            // If project_code exists, update or create by project_code; otherwise by name
                            $searchCriteria = ! empty($projectCode)
                                ? ['project_code' => $projectCode]
                                : ['name' => $name];

                            Project::updateOrCreate(
                                $searchCriteria,
                                [
                                    'project_code' => $projectCode,
                                    'name'         => $name,
                                    'datecovered'  => $datecovered,
                                    'scope'        => $scope,
                                    'address'      => $address,
                                    'status'       => true,
                                ]
                            );

                            $importedCount++;
                        }
                    }

                    fclose($file);

                    // Delete temporary uploaded file
                    Storage::disk('public')->delete($data['dat_file']);

                    // Log User Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Uploaded CSV file to import/update {$importedCount} projects",
                        'module'    => 'Project Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('CSV Import Completed')
                        ->body("Successfully imported {$importedCount} projects.")
                        ->success()
                        ->send();
                }),
            Action::make('exportProjectsCsvFormat')
                ->label('Export Projects CSV Format')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->size('xs')
                ->outlined()
                ->url(route('projects.export.csv'))
                ->openUrlInNewTab(),
            CreateAction::make()
                ->label('New Project')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle'),

        ];
    }
}
