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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        // Inside your table headerActions or ListRecords getHeaderActions:
        $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
        $isOnline = $hasInternet ? (fclose($hasInternet) || true) : false;

        return [
            // Add these inside your header actions array:
            // Action::make('syncProjectsToCloud')
            //     ->label('Upload Projects to Cloud')
            //     ->icon($isOnline ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-x-mark')
            //     ->color($isOnline ? 'success' : 'danger')
            //     ->size('xs')
            //     ->outlined()
            //     ->requiresConfirmation()
            //     ->modalHeading('Upload Projects to Cloud')
            //     ->modalDescription('This will push all local projects to the cloud database. Proceed?')
            //     ->modalSubmitActionLabel('Yes, upload')
            //     ->visible(fn() => app()->environment('local'))
            //     ->action(function () {
            //         try {
            //             $projects = Project::all()->toArray();
            //             if (empty($projects)) {
            //                 Notification::make()->title('No local projects found to upload.')->warning()->send();
            //                 return;
            //             }
            //             // $pushUrl = str_replace('sync-attendance', 'sync-projects', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-projects'));
            //             // $response = Http::timeout(30)->post($pushUrl, ['projects' => $projects]);
            //             $pushUrl = str_replace('sync-attendance', 'sync-projects', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-projects'));
            //             $response = Http::withToken(env('CLOUD_API_TOKEN'))
            //                 ->timeout(30)
            //                 ->post($pushUrl, ['projects' => $projects]);
            //             if ($response->successful()) {
            //                 Notification::make()->title('Projects uploaded successfully!')->success()->send();
            //             } else {
            //                 throw new \Exception('Cloud server error: ' . $response->status());
            //             }
            //         } catch (\Exception $e) {
            //             Notification::make()->title('Upload failed: ' . $e->getMessage())->danger()->send();
            //         }
            //     }),
            // Inside your header actions array:
            Action::make('syncProjectsToCloud')
                ->label('Upload Projects to Cloud')
                ->icon($isOnline ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-x-mark')
                ->color($isOnline ? 'success' : 'danger')
                ->size('xs')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Upload Projects to Cloud')
                ->modalDescription('This will push all local projects to the cloud database. Proceed?')
                ->modalSubmitActionLabel('Yes, upload')
                ->visible(fn() => app()->environment('local'))
                ->action(function () {
                    try {
                        $projects = Project::all()->toArray();
                        if (empty($projects)) {
                            Notification::make()->title('No local projects found to upload.')->warning()->send();
                            return;
                        }

                        $pushUrl = str_replace('sync-attendance', 'sync-projects', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-projects'));
                        $response = Http::withToken(env('CLOUD_API_TOKEN'))
                            ->timeout(30)
                            ->post($pushUrl, ['projects' => $projects]);

                        if ($response->successful()) {
                            Notification::make()->title('Projects uploaded successfully!')->success()->send();
                        } else {
                            $errorMsg = $response->json('message') ?? ('Cloud server error: ' . $response->status());
                            throw new \Exception($errorMsg);
                        }
                    } catch (\Exception $e) {
                        Notification::make()->title('Upload failed: ' . $e->getMessage())->danger()->send();
                    }
                }),

            Action::make('pullProjectsFromCloud')
                ->label('Download Projects from Cloud')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->size('xs')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Download Projects from Cloud')
                ->modalDescription('This will download and merge all cloud projects into your local database. Proceed?')
                ->modalSubmitActionLabel('Yes, download')
                ->visible(fn() => app()->environment('local') && $isOnline)
                ->action(function () {
                    try {
                        // $pullUrl = str_replace('sync-attendance', 'fetch-cloud-projects', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/fetch-cloud-projects'));
                        // $response = Http::timeout(30)->get($pullUrl);
                        $pullUrl = str_replace('sync-attendance', 'fetch-cloud-projects', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/fetch-cloud-projects'));
                        $response = Http::withToken(env('CLOUD_API_TOKEN'))
                            ->timeout(30)
                            ->get($pullUrl);

                        if (!$response->successful()) {
                            throw new \Exception('Cloud server error: ' . $response->status());
                        }

                        $cloudProjects = $response->json('projects', []);
                        $downloadedCount = 0;

                        foreach ($cloudProjects as $cloudProject) {
                            $code = trim($cloudProject['project_code']);
                            $normalizedCode = Str::upper(preg_replace('/\s+/', '', $code));

                            $localProject = Project::whereRaw("UPPER(REPLACE(project_code, ' ', '')) = ?", [$normalizedCode])->first();

                            if (!$localProject) {
                                Project::create([
                                    'project_code' => Str::upper($code),
                                    'name'         => Str::upper($cloudProject['name'] ?? ''),
                                    'datecovered'  => Str::upper($cloudProject['datecovered'] ?? ''),
                                    'scope'        => Str::upper($cloudProject['scope'] ?? ''),
                                    'address'      => Str::upper($cloudProject['address'] ?? ''),
                                    'image'        => $cloudProject['image'] ?? null,
                                    'status'       => $cloudProject['status'] ?? true,
                                    'created_at'   => $cloudProject['created_at'] ?? now(),
                                    'updated_at'   => $cloudProject['updated_at'] ?? now(),
                                ]);
                                $downloadedCount++;
                            } else {
                                $localProject->update([
                                    'name'         => Str::upper($cloudProject['name'] ?? ''),
                                    'datecovered'  => Str::upper($cloudProject['datecovered'] ?? ''),
                                    'scope'        => Str::upper($cloudProject['scope'] ?? ''),
                                    'address'      => Str::upper($cloudProject['address'] ?? ''),
                                    'image'        => $cloudProject['image'] ?? null,
                                    'status'       => $cloudProject['status'] ?? true,
                                    'updated_at'   => $cloudProject['updated_at'] ?? now(),
                                ]);
                                $downloadedCount++;
                            }
                        }

                        Notification::make()
                            ->title('Download successful!')
                            ->body("Successfully synchronized {$downloadedCount} projects from the cloud.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Download failed: ' . $e->getMessage())->danger()->send();
                    }
                }),
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
