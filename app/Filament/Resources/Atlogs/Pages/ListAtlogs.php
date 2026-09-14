<?php

namespace App\Filament\Resources\Atlogs\Pages;

use App\Filament\Resources\Atlogs\AtlogResource;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\ActivityLog;
use App\Models\Atlog;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListAtlogs extends ListRecords
{
    protected static string $resource = AtlogResource::class;
    // 👇 1. Place the helper function right here in the class
    protected function getSyncGaps(): array
    {
        try {
            $localLogs = Atlog::all(['user_id', 'recorded_at', 'updated_at'])->keyBy(function ($item) {
                return $item->user_id . '|' . Carbon::parse($item['recorded_at'])->format('Y-m-d H:i:s');
            });

            $metadataUrl = str_replace('sync-attendance', 'attendance-metadata', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/attendance-metadata'));

            $response = Http::timeout(2)
                ->withHeaders(['X-Sync-Token' => env('SYNC_API_TOKEN')])
                ->get($metadataUrl);

            if (!$response->successful()) {
                return ['upload' => Atlog::count(), 'download' => 0, 'online' => false];
            }

            $cloudLogs = collect($response->json('metadata', []))->keyBy(function ($item) {
                return $item['user_id'] . '|' . Carbon::parse($item['recorded_at'])->format('Y-m-d H:i:s');
            });

            $uploadCount = 0;
            $downloadCount = 0;

            foreach ($localLogs as $key => $local) {
                if (!isset($cloudLogs[$key])) {
                    $uploadCount++;
                } else {
                    $cloudUpdated = Carbon::parse($cloudLogs[$key]['updated_at']);
                    $localUpdated = Carbon::parse($local->updated_at);

                    if ($localUpdated->greaterThan($cloudUpdated)) {
                        $uploadCount++;
                    } elseif ($cloudUpdated->greaterThan($localUpdated)) {
                        $downloadCount++;
                    }
                }
            }

            foreach ($cloudLogs as $key => $cloud) {
                if (!isset($localLogs[$key])) {
                    $downloadCount++;
                }
            }

            return [
                'upload' => $uploadCount,
                'download' => $downloadCount,
                'online' => true,
            ];
        } catch (\Exception $e) {
            return ['upload' => Atlog::count(), 'download' => 0, 'online' => false];
        }
    }
    protected function getHeaderActions(): array
    {
        return [
            // 🔄 Bi-Directional Sync (Local <-> Cloud) Action (Visible ONLY in Local Environment)
            Action::make('syncToCloud')
                ->badge(function () {
                    return \App\Models\Atlog::count(); // Shows the number of local records
                })
                ->badgeColor(function () {
                    $count = \App\Models\Atlog::count();
                    // Change badge color depending on how many logs are queued up
                    return $count > 0 ? 'warning' : 'gray';
                })
                ->label(function () {
                    // Quick socket check to verify internet
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return "Sync Local to Cloud";
                    }
                    return 'No Internet Connection';
                })
                ->icon(function () {
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return 'heroicon-o-cloud-arrow-up'; // Green state icon
                    }
                    return 'heroicon-o-x-mark'; // ❌ Red state icon for no internet
                })
                ->color(function () {
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return 'success'; // 🟢 Green when online
                    }
                    return 'danger'; // 🔴 Red when offline
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Local Attendance to Cloud')
                ->modalDescription('This will securely push all local attendance logs to your cloud application via API. Proceed?')
                ->modalSubmitActionLabel('Yes, start sync')
                // ->visible(fn() => app()->environment('local'))
                ->visible(function () {
                    // First, check if it's the local environment
                    if (!app()->environment('local')) {
                        return false;
                    }
                    // Second, perform a quick 1-second timeout socket check for internet
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return true; // Visible when local AND online
                    }

                    return false; // Hidden when offline
                })
                ->action(function () {
                    try {
                        // Fetch local logs
                        $localLogs = Atlog::all()->toArray();

                        if (empty($localLogs)) {
                            Notification::make()
                                ->title('No local attendance logs found to sync.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Send data securely via HTTPS to your cloud app API using the .env variable
                        $response = Http::timeout(30)->post(env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-attendance'), [
                            'logs' => $localLogs
                        ]);

                        if ($response->successful()) {
                            Notification::make()
                                ->title('Cloud sync successful!')
                                ->body($response->json('message', 'Records synchronized.'))
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Cloud server returned error code: ' . $response->status());
                        }
                    } catch (\Exception $e) {
                        Log::error('API cloud sync failed: ' . $e->getMessage());

                        Notification::make()
                            ->title('Sync failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('pullFromCloud')
                ->label(function () {
                    // Quick connection check (1 second timeout so it doesn't lag the UI)
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return 'Pull Cloud to Local';
                    }
                    return 'No Internet Connection';
                })
                ->icon(function () {
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return 'heroicon-o-cloud-arrow-down'; // Green state icon
                    }
                    return 'heroicon-o-x-mark'; // ❌ Red state icon for no internet
                })
                ->color(function () {
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return 'info'; // 🟢 Green when online
                    }
                    return 'danger'; // 🔴 Red when offline
                })
                ->badge(function () {
                    try {
                        $countUrl = str_replace('sync-attendance', 'attendance-count', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/attendance-count'));
                        $response = Http::timeout(1)->get($countUrl);
                        return $response->successful() ? $response->json('count', 0) : 0;
                    } catch (\Exception $e) {
                        return 0;
                    }
                })
                ->badgeColor(function () {
                    try {
                        $countUrl = str_replace('sync-attendance', 'attendance-count', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/attendance-count'));
                        $response = Http::timeout(1)->get($countUrl);
                        $count = $response->successful() ? $response->json('count', 0) : 0;
                        return $count > 0 ? 'success' : 'gray';
                    } catch (\Exception $e) {
                        return 'gray';
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Pull Attendance from Cloud')
                ->modalDescription('This will fetch cloud records and update your local database with newer or missing data. Proceed?')
                ->modalSubmitActionLabel('Yes, pull data')
                // ->visible(fn() => app()->environment('local'))
                ->visible(function () {
                    // First, check if it's the local environment
                    if (!app()->environment('local')) {
                        return false;
                    }

                    // Second, perform a quick 1-second timeout socket check for internet
                    $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
                    if ($hasInternet) {
                        fclose($hasInternet);
                        return true; // Visible when local AND online
                    }

                    return false; // Hidden when offline
                })
                ->action(function () {
                    try {
                        // Request cloud logs via HTTP GET
                        $apiUrl = str_replace('sync-attendance', 'fetch-cloud-attendance', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/fetch-cloud-attendance'));
                        $response = Http::timeout(30)->get($apiUrl);

                        if (!$response->successful()) {
                            throw new \Exception('Cloud server returned error code: ' . $response->status());
                        }

                        $cloudLogs = $response->json('logs', []);
                        $pulledCount = 0;

                        foreach ($cloudLogs as $cloudLog) {
                            $recordedAt = Carbon::parse($cloudLog['recorded_at'])->format('Y-m-d H:i:s');
                            $cloudUpdated = isset($cloudLog['updated_at']) ? Carbon::parse($cloudLog['updated_at']) : null;

                            // Check if record exists locally
                            $localLog = Atlog::where('user_id', $cloudLog['user_id'])
                                ->where('recorded_at', $recordedAt)
                                ->first();

                            if (!$localLog) {
                                // Missing locally, insert it
                                Atlog::create([
                                    'user_id'           => $cloudLog['user_id'],
                                    'project_code'      => $cloudLog['project_code'] ?? 0,
                                    'recorded_at'       => $recordedAt,
                                    'status'            => $cloudLog['status'],
                                    'verification_mode' => $cloudLog['verification_mode'],
                                    'work_code'         => $cloudLog['work_code'] ?? 0,
                                    'reserved'          => $cloudLog['reserved'] ?? 0,
                                    'created_at'        => $cloudLog['created_at'],
                                    'updated_at'        => $cloudLog['updated_at'] ?? now(),
                                ]);
                                $pulledCount++;
                            } elseif ($cloudUpdated && $cloudUpdated->greaterThan(Carbon::parse($localLog->updated_at))) {
                                // Cloud is newer than local, update local record
                                $localLog->update([
                                    'project_code'      => $cloudLog['project_code'] ?? 0,
                                    'status'            => $cloudLog['status'],
                                    'verification_mode' => $cloudLog['verification_mode'],
                                    'work_code'         => $cloudLog['work_code'] ?? 0,
                                    'reserved'          => $cloudLog['reserved'] ?? 0,
                                    'updated_at'        => $cloudLog['updated_at'],
                                ]);
                                $pulledCount++;
                            }
                        }

                        Notification::make()
                            ->title('Reverse sync successful!')
                            ->body("Successfully updated local database with {$pulledCount} records from the cloud.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('API pull from cloud failed: ' . $e->getMessage());

                        Notification::make()
                            ->title('Pull failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('backaction')
                ->label('Back')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-arrow-left')
                ->visible(
                    fn() =>
                    session()->has('session_employeestatus') &&
                        session()->has('session_employeetype') &&
                        session()->has('session_periodcode')
                )
                ->action(function () {
                    $status = session('session_employeestatus');
                    $type = session('session_employeetype');
                    $code = session('session_periodcode');
                    session()->forget([
                        'session_employeetype',
                        'session_employeestatus',
                        'session_periodcode',
                        'session_employee_id',
                    ]);
                    session([
                        'session_employeestatus' => $status,
                        'session_employeetype' => $type,
                        'session_periodcode' => $code,
                    ]);
                    // 4. Redirect cleanly without appending any visible URL query parameters
                    return redirect()->to(PayrollResource::getUrl('index'));
                }),



            CreateAction::make()
                ->label('New Atlog')
                ->color('success')
                ->size('xs')
                ->after(function ($record) {
                    // 1. Log Manual Atlog Creation
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Manually created Biometric Log for User ID: [{$record->user_id}] | Project: {$record->project_code} | Recorded At: {$record->recorded_at}",
                        'module'    => 'Timekeeping Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                })
                ->outlined()
                ->icon('heroicon-m-plus-circle'),


            Action::make('importAtlog')
                ->label('Import .DAT File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->hidden(fn() => filled(session('session_employee_id')))
                ->form([
                    Section::make('Import Biometric Logs')
                        ->description('Upload your raw log data and assign it to a specific project.')
                        ->icon('heroicon-o-arrow-up-tray') // Optional: Adds a sleek icon to the header
                        ->extraAttributes([
                            'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                        ])
                        ->schema([
                            // Inline Warning Banner using Placeholder
                            Placeholder::make('duplicate_warning_message')
                                ->label(false)
                                ->content(new HtmlString("
                            <div class='p-5 border border-warning-500 rounded-xl bg-warning-50 dark:bg-warning-950/20 flex items-start gap-4'>
                                <div style='color: #eab308; margin-block-start: 0.125rem; flex-shrink: 0; inline-size: 1.25rem; block-size: 1.25rem;'>
                                    <svg style='inline-size: 100%; block-size: 100%;' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 style='font-weight: 700; color: #854d0e; font-size: 1rem; letter-spacing: 0.025em; line-height: 1.5;'>Duplicate Prevention Enabled</h4>
                                    <p style='font-size: 0.875rem; color: #a16207; line-height: 1.6; margin-block-start: 0.35rem;'>Rows with an existing combination of <strong>User ID</strong> and <strong>Recorded At</strong> timestamp will be automatically skipped during import.</p>
                                </div>
                            </div>
                        ")),
                            // 1. Added Project Select Component
                            Select::make('project_code')
                                ->label('Project')
                                ->options(Project::all()->pluck('name', 'project_code'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            FileUpload::make('attlog_file')
                                ->label('Biometric Log File')
                                ->required()
                                ->disk('local')
                                ->directory('imports')
                                ->storeFiles(true)
                                ->rules(['file', 'extensions:dat,txt'])
                        ])
                ])
                ->action(function (array $data) {
                    $disk = 'local';
                    $filePath = Storage::disk($disk)->path($data['attlog_file']);
                    $projectCode = $data['project_code'];

                    if (!file_exists($filePath)) {
                        Notification::make()->title('File Error')->danger()->send();
                        return;
                    }

                    // 1. First pass: Parse and clean lines into memory fast
                    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (empty($lines)) {
                        Storage::disk($disk)->delete($data['attlog_file']);
                        Notification::make()->title('File is empty')->warning()->send();
                        return;
                    }

                    $rawRows = [];
                    $userIds = [];
                    $timestamps = [];

                    foreach ($lines as $line) {
                        $parts = explode("\t", trim($line));
                        if (count($parts) >= 2) {
                            $userId = trim($parts[0]);
                            $recordedAt = Carbon::parse($parts[1])->toDateTimeString();

                            $rawRows[] = [
                                'user_id'           => $userId,
                                'project_code'      => $projectCode,
                                'recorded_at'       => $recordedAt,
                                'status'            => (int) ($parts[2] ?? 0),
                                'verification_mode' => (int) ($parts[3] ?? 0),
                                'work_code'         => (int) ($parts[4] ?? 0),
                                'reserved'          => (int) ($parts[5] ?? 0),
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ];

                            $userIds[$userId] = true;
                            $timestamps[] = $recordedAt;
                        }
                    }

                    if (empty($rawRows)) {
                        Storage::disk($disk)->delete($data['attlog_file']);
                        Notification::make()->title('No valid records found in file')->warning()->send();
                        return;
                    }

                    // 2. Fetch existing records in 1 single DB query (O(1) Hash Map)
                    $minDate = min($timestamps);
                    $maxDate = max($timestamps);

                    $existingKeys = Atlog::whereIn('user_id', array_keys($userIds))
                        ->whereBetween('recorded_at', [$minDate, $maxDate])
                        ->get(['user_id', 'recorded_at'])
                        ->mapWithKeys(fn($item) => ["{$item->user_id}_{$item->recorded_at}" => true])
                        ->toArray();

                    // 3. Filter out duplicates instantly in memory
                    $batch = [];
                    $count = 0;
                    $skippedCount = 0;

                    foreach ($rawRows as $row) {
                        $key = "{$row['user_id']}_{$row['recorded_at']}";

                        if (isset($existingKeys[$key])) {
                            $skippedCount++;
                            continue;
                        }

                        // Prevent duplicate rows within the same uploaded file
                        $existingKeys[$key] = true;
                        $batch[] = $row;
                        $count++;
                    }

                    // 4. Bulk insert in chunks of 1000
                    if (!empty($batch)) {
                        DB::transaction(function () use ($batch) {
                            foreach (array_chunk($batch, 1000) as $chunk) {
                                Atlog::insert($chunk);
                            }
                        });
                    }

                    Storage::disk($disk)->delete($data['attlog_file']);

                    // Log Activity & Send Notification
                    $activityLogMessage = "Imported raw biometric file to project: [{$projectCode}]. Added: {$count} records";
                    if ($skippedCount > 0) {
                        $activityLogMessage .= " | Skipped: {$skippedCount} duplicates";
                    }

                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => $activityLogMessage,
                        'module'    => 'Timekeeping Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);

                    $bodyMessage = "Successfully imported <strong style='font-weight: 700;'>{$count}</strong> records.";
                    if ($skippedCount > 0) {
                        $bodyMessage .= "<br><span style='color: #d97706; font-weight: 500;'>Skipped {$skippedCount} duplicate rows.</span>";
                    }

                    Notification::make()
                        ->title('Import Completed')
                        ->body(new HtmlString($bodyMessage))
                        ->success()
                        ->send();
                }),


            Action::make('clearAllData')
                ->label('Clear All Data')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Truncate Table')
                ->modalDescription('Are you sure you want to delete ALL records? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete everything')
                ->action(function () {
                    // Truncate table cleanly
                    Atlog::truncate();

                    Notification::make()
                        ->title('Table Cleared')
                        ->body('All biometric log records have been successfully deleted.')
                        ->success()
                        ->send();
                }),



        ];
    }
}
