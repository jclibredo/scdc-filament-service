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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListAtlogs extends ListRecords
{
    protected static string $resource = AtlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Action::make('back_to_billing')
            //     ->label('Back')
            //     ->color('success')
            //     ->icon('heroicon-m-arrow-left')
            //     // ->visible(fn() => request()->query('type') === 'payroll')
            //     ->url(function () {
            //         return PayrollResource::getUrl('index');
            //         // session()->forget(['type', 'session_employee_id']);
            //         // return PayrollResource::getUrl('index', [
            //         //     'session_employee_id' => $this->record?->user_id,
            //         // ]);
            //     }),
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
                                <div style='color: #eab308; margin-top: 0.125rem; flex-shrink: 0; width: 1.25rem; height: 1.25rem;'>
                                    <svg style='width: 100%; height: 100%;' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 style='font-weight: 700; color: #854d0e; font-size: 1rem; letter-spacing: 0.025em; line-height: 1.5;'>Duplicate Prevention Enabled</h4>
                                    <p style='font-size: 0.875rem; color: #a16207; line-height: 1.6; margin-top: 0.35rem;'>Rows with an existing combination of <strong>User ID</strong> and <strong>Recorded At</strong> timestamp will be automatically skipped during import.</p>
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
