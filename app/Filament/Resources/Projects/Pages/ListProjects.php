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
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                        ->storeFiles(false)
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
                    $fileInput = $data['dat_file'];
                    if (is_array($fileInput)) {
                        $fileInput = reset($fileInput);
                    }
                    $filePath = null;
                    if ($fileInput instanceof TemporaryUploadedFile) {
                        $filePath = $fileInput->getRealPath();
                    } elseif (is_string($fileInput)) {
                        $possiblePaths = [
                            $fileInput,
                            storage_path('app/livewire-tmp/' . basename($fileInput)),
                            storage_path('app/public/' . $fileInput),
                            storage_path('app/' . $fileInput),
                            Storage::disk('public')->path($fileInput),
                        ];
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $filePath = $path;
                                break;
                            }
                        }
                    }
                    if (! $filePath || ! file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found!')
                            ->body('Unable to locate or open the uploaded file.')
                            ->danger()
                            ->send();
                        return;
                    }
                    $fileStream = fopen($filePath, 'r');
                    if (! $fileStream) {
                        Notification::make()
                            ->title('Unable to read file')
                            ->body('Failed to open file stream.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $rawHeader = fgetcsv($fileStream);
                    if (! $rawHeader) {
                        fclose($fileStream);
                        Notification::make()
                            ->title('Invalid CSV File')
                            ->body('The uploaded CSV file is empty or corrupt.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Sanitize header keys
                    $header = array_map(function ($h) {
                        $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);
                        return strtolower(trim($h));
                    }, $rawHeader);

                    $importedCount = 0;
                    $skippedCount  = 0;
                    $failedRows    = [];
                    while (($row = fgetcsv($fileStream)) !== false) {
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        // Ensure row column count matches header column count
                        if (count($row) < count($header)) {
                            $row = array_pad($row, count($header), '');
                        }
                        $rowData = array_combine($header, array_slice($row, 0, count($header)));
                        // Raw inputs
                        $projectCode = trim($rowData['project_code'] ?? '');
                        $name        = trim($rowData['name'] ?? '');
                        $datecovered = trim($rowData['datecovered'] ?? '');
                        $scope       = trim($rowData['scope'] ?? '');
                        $address     = Str::upper(trim($rowData['address'] ?? ''));

                        // === PRE-CHECK: Check if Record Already Exists ===
                        // Normalize CSV values (UPPERCASE & REMOVE ALL SPACES)
                        $normalizedCsvCode = Str::upper(preg_replace('/\s+/', '', $projectCode));
                        $normalizedCsvName = Str::upper(preg_replace('/\s+/', '', $name));
                        $isDuplicate = false;
                        if (!empty($normalizedCsvCode) && !empty($normalizedCsvName)) {
                            $isDuplicate = Project::whereRaw("UPPER(REPLACE(project_code, ' ', '')) = ?", [$normalizedCsvCode])
                                ->whereRaw("UPPER(REPLACE(name, ' ', '')) = ?", [$normalizedCsvName])
                                ->exists();
                        }

                        // If it already exists in DB, skip without adding to the error download list
                        if ($isDuplicate) {
                            $skippedCount++;
                            continue;
                        }

                        // Collect validation errors for current row
                        $rowErrors = [];

                        // Perform regular validations
                        $validator = Validator::make([
                            'project_code' => Str::upper($projectCode),
                            'name'         => Str::upper($name),
                            'datecovered'  => Str::upper($datecovered),
                            'scope'        => Str::upper($scope),
                            'address'      => Str::upper($address),
                        ], [
                            'project_code' => ['required', 'unique:projects,project_code'],
                            'name'         => ['required', 'unique:projects,name'],
                        ]);
                        if ($validator->fails()) {
                            foreach ($validator->errors()->all() as $err) {
                                $rowErrors[] = $err;
                            }
                        }
                        // If there are validation errors, add row to download file
                        if (! empty($rowErrors)) {
                            $failedRow   = $row;
                            $failedRow[] = implode(' | ', $rowErrors);
                            $failedRows[] = $failedRow;
                            continue;
                        }
                        // Create Project Record
                        Project::create([
                            'project_code' => Str::upper($projectCode),
                            'name'         => Str::upper($name),
                            'datecovered'  => Str::upper($datecovered),
                            'scope'        => Str::upper($scope),
                            'address'      => Str::upper($address),
                            'status'       => true,
                        ]);
                        $importedCount++;
                    }
                    fclose($fileStream);
                    // Cleanup temporary stored file
                    if (is_string($fileInput) && Storage::disk('public')->exists($fileInput)) {
                        Storage::disk('public')->delete($fileInput);
                    }
                    // Log User Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Imported {$importedCount} projects, skipped {$skippedCount} existing, " . count($failedRows) . " failed validation",
                        'module'    => 'Project Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                    // Trigger download ONLY for rows that had validation errors
                    if (count($failedRows) > 0) {
                        $failedHeader = array_merge($rawHeader, ['Errors to fix']);
                        $callback = function () use ($failedHeader, $failedRows) {
                            $output = fopen('php://output', 'w');
                            fputcsv($output, $failedHeader);
                            foreach ($failedRows as $failedRow) {
                                fputcsv($output, $failedRow);
                            }
                            fclose($output);
                        };
                        Notification::make()
                            ->title('Import Completed with Validation Errors')
                            ->body("Imported {$importedCount} projects. Skipped {$skippedCount} existing records. " . count($failedRows) . " rows failed validation and will download automatically.")
                            ->warning()
                            ->send();
                        return response()->streamDownload(
                            $callback,
                            'project_import_errors_' . now()->format('Y_m_d_His') . '.csv',
                            ['Content-Type' => 'text/csv']
                        );
                    }

                    Notification::make()
                        ->title('CSV Import Complete')
                        ->body("Successfully imported {$importedCount} new projects. Skipped {$skippedCount} existing records.")
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
