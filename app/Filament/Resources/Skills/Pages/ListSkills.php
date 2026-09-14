<?php

namespace App\Filament\Resources\Skills\Pages;

use App\Filament\Resources\Skills\SkillResource;
use App\Models\ActivityLog;
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
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListSkills extends ListRecords
{
    protected static string $resource = SkillResource::class;

    protected function getHeaderActions(): array
    {
        // Simple connectivity check for the upload button state
        // $hasInternet = @fsockopen('scdc-web-app.com', 443, $errno, $errstr, 1);
        // if ($hasInternet) {
        //     fclose($hasInternet);
        //     $isOnline = true;
        // } else {
        //     $isOnline = false;
        // }
        return [

            // // 2. Simple Upload (Local Skills to Cloud)
            // Action::make('syncSkillsToCloud')
            //     ->label('Upload Skills to Cloud')
            //     ->icon($isOnline ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-x-mark')
            //     ->color($isOnline ? 'success' : 'danger')
            //     ->size(Size::ExtraSmall)
            //     ->requiresConfirmation()
            //     ->modalHeading('Upload Skills to Cloud')
            //     ->modalDescription('This will push all local skills to the cloud database. Proceed?')
            //     ->modalSubmitActionLabel('Yes, upload')
            //     ->visible(fn() => app()->environment('local'))
            //     ->action(function () {
            //         try {
            //             $skills = Skill::all()->toArray();
            //             if (empty($skills)) {
            //                 Notification::make()->title('No local skills found to upload.')->warning()->send();
            //                 return;
            //             }
            //             // // --- UPLOAD ACTION ---
            //             $pushUrl = str_replace('sync-attendance', 'sync-skills', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/sync-skills'));
            //             $response = Http::withToken(env('CLOUD_API_TOKEN'))
            //                 ->timeout(30)
            //                 ->post($pushUrl, ['skills' => $skills]); // or ['skills' => $skills]

            //             if ($response->successful()) {
            //                 Notification::make()->title('Skills uploaded successfully!')->success()->send();
            //             } else {
            //                 throw new \Exception('Cloud server error: ' . $response->status());
            //             }
            //         } catch (\Exception $e) {
            //             Notification::make()->title('Upload failed: ' . $e->getMessage())->danger()->send();
            //         }
            //     }),

            // // 3. Simple Download (Cloud Skills to Local)
            // Action::make('pullSkillsFromCloud')
            //     ->label('Download Skills from Cloud')
            //     ->icon('heroicon-o-cloud-arrow-down')
            //     ->color('info')
            //     ->size(Size::ExtraSmall)
            //     ->requiresConfirmation()
            //     ->modalHeading('Download Skills from Cloud')
            //     ->modalDescription('This will download and merge all cloud skills into your local database. Proceed?')
            //     ->modalSubmitActionLabel('Yes, download')
            //     ->visible(fn() => app()->environment('local') && $isOnline)
            //     ->action(function () {
            //         try {
            //             // $pullUrl = str_replace('sync-attendance', 'fetch-cloud-skills', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/fetch-cloud-skills'));
            //             // $response = Http::timeout(30)->get($pullUrl);

            //             // --- DOWNLOAD ACTION ---
            //             $pullUrl = str_replace('sync-attendance', 'fetch-cloud-skills', env('CLOUD_API_URL', 'https://scdc-web-app.com/api/fetch-cloud-skills'));
            //             $response = Http::withToken(env('CLOUD_API_TOKEN'))
            //                 ->timeout(30)
            //                 ->get($pullUrl);


            //             if (!$response->successful()) {
            //                 throw new \Exception('Cloud server error: ' . $response->status());
            //             }

            //             $cloudSkills = $response->json('skills', []);
            //             $downloadedCount = 0;

            //             foreach ($cloudSkills as $cloudSkill) {
            //                 $title = trim($cloudSkill['title']);
            //                 $normalizedTitle = Str::upper(preg_replace('/\s+/', '', $title));

            //                 // Find local skill ignoring spacing/casing differences
            //                 $localSkill = Skill::whereRaw("UPPER(REPLACE(title, ' ', '')) = ?", [$normalizedTitle])->first();

            //                 if (!$localSkill) {
            //                     Skill::create([
            //                         'title'      => Str::upper($title),
            //                         'details'    => $cloudSkill['details'] ?? null,
            //                         'status'     => $cloudSkill['status'] ?? true,
            //                         'created_at' => $cloudSkill['created_at'] ?? now(),
            //                         'updated_at' => $cloudSkill['updated_at'] ?? now(),
            //                     ]);
            //                     $downloadedCount++;
            //                 } else {
            //                     // Update local record with cloud data
            //                     $localSkill->update([
            //                         'details'    => $cloudSkill['details'] ?? null,
            //                         'status'     => $cloudSkill['status'] ?? true,
            //                         'updated_at' => $cloudSkill['updated_at'] ?? now(),
            //                     ]);
            //                     $downloadedCount++;
            //                 }
            //             }

            //             Notification::make()
            //                 ->title('Download successful!')
            //                 ->body("Successfully synchronized {$downloadedCount} skills from the cloud.")
            //                 ->success()
            //                 ->send();
            //         } catch (\Exception $e) {
            //             Notification::make()->title('Download failed: ' . $e->getMessage())->danger()->send();
            //         }
            //     }),

            Action::make('importSkillsCsvFormat')
                ->label('Import .CSV File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(Size::ExtraSmall)
                ->outlined()
                ->form([
                    FileUpload::make('dat_file')
                        ->label('Select CSV File')
                        ->required()
                        ->storeFiles(false)
                        ->disk('public') // Explicitly set storage disk
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'text/comma-separated-values',
                        ])
                        ->maxSize(10240) // 10 MB
                        ->helperText('Please upload a .CSV file containing "title" and "details" headers.'),
                ])
                ->action(function (array $data) {
                    $fileInput = $data['dat_file'];
                    // Extract single item if array
                    if (is_array($fileInput)) {
                        $fileInput = reset($fileInput);
                    }
                    $filePath = null;
                    // 1. Resolve file path across Livewire temporary and storage disks
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
                    // 2. Open stream directly from physical path
                    $fileStream = fopen($filePath, 'r');
                    if (! $fileStream) {
                        Notification::make()
                            ->title('Unable to read file')
                            ->body('Failed to open file stream.')
                            ->danger()
                            ->send();
                        return;
                    }
                    // Parse CSV Header
                    $header = fgetcsv($fileStream);
                    if (! $header) {
                        fclose($fileStream);
                        Notification::make()
                            ->title('Invalid CSV File')
                            ->body('The uploaded CSV file is empty or corrupt.')
                            ->danger()
                            ->send();
                        return;
                    }
                    // Sanitize headers to lower case and remove BOM characters
                    $header = array_map(function ($h) {
                        $h = preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF]/s', '', $h);

                        return strtolower(trim($h));
                    }, $header);
                    $importedCount = 0;
                    $skippedCount  = 0;
                    while (($row = fgetcsv($fileStream)) !== false) {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        // Combine header with current row data
                        $rowData = array_combine($header, $row);
                        $rawTitle = trim($rowData['title'] ?? '');
                        $details  = Str::upper(trim($rowData['details'] ?? ''));
                        if (empty($rawTitle)) {
                            continue;
                        }
                        // Normalize CSV title: convert to UPPERCASE and REMOVE ALL SPACES
                        $normalizedCsvTitle = Str::upper(preg_replace('/\s+/', '', $rawTitle));
                        // === SKIP CHECK: Normalize DB title (uppercase & remove spaces) for comparison ===
                        $exists = Skill::whereRaw("UPPER(REPLACE(title, ' ', '')) = ?", [$normalizedCsvTitle])->exists();
                        if ($exists) {
                            $skippedCount++;
                            continue; // Skip existing record
                        }
                        // 3. Create new Skill entry
                        Skill::create([
                            'title'   => Str::upper($rawTitle),
                            'details' => $details,
                            'status'  => true, // Default status to active
                        ]);
                        $importedCount++;
                    }
                    fclose($fileStream);
                    // Clean up stored disk file if applicable
                    if (is_string($fileInput) && Storage::disk('public')->exists($fileInput)) {
                        Storage::disk('public')->delete($fileInput);
                    }
                    // Log User Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Uploaded CSV file to import {$importedCount} skills (skipped {$skippedCount} existing)",
                        'module'    => 'Skill Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                    // 4. Send success notification
                    Notification::make()
                        ->title('CSV Import Completed')
                        ->body("Successfully imported {$importedCount} skills. Skipped {$skippedCount} existing skills.")
                        ->success()
                        ->send();
                }),
            Action::make('exportSkillsCsvFormat')
                ->label('Export Skills CSV Format')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->size('xs')
                ->outlined()
                ->url(route('skills.export.csv'))
                ->openUrlInNewTab(),
            CreateAction::make()
                ->label('New Skill')
                ->button()
                ->visible(fn() => app()->environment('local'))
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-plus-circle')
                ->after(function ($record) {
                    // Log the skill creation activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Created a new skill: {$record->title} (ID: {$record->id})",
                        'module'    => 'Skill Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                }),
        ];
    }
}
