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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListSkills extends ListRecords
{
    protected static string $resource = SkillResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // Action::make('importSkillsCsvFormat')
            //     ->label('Import .CSV File')
            //     ->icon('heroicon-o-arrow-up-tray')
            //     ->color('success')
            //     ->size(Size::ExtraSmall)
            //     ->outlined()
            //     ->form([
            //         FileUpload::make('dat_file')
            //             ->label('Select CSV File')
            //             ->required()
            //             ->storeFiles(false)
            //             ->disk('public') // Explicitly set storage disk
            //             ->directory('imports')
            //             ->acceptedFileTypes([
            //                 'text/csv',
            //                 'text/plain',
            //                 'application/csv',
            //                 'text/comma-separated-values',
            //             ])
            //             ->maxSize(10240) // 10 MB
            //             ->helperText('Please upload a .CSV file containing "title" and "details" headers.'),
            //     ])
            //     ->action(function (array $data) {
            //         // 1. Get uploaded file path from storage
            //         $filePath = Storage::disk('public')->path($data['dat_file']);
            //         if (! file_exists($filePath)) {
            //             Notification::make()
            //                 ->title('File not found!')
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }
            //         // 2. Open and parse CSV file
            //         $file = fopen($filePath, 'r');
            //         $header = fgetcsv($file);
            //         // Sanitize headers to lower case (e.g. "Title" -> "title")
            //         $header = array_map(fn($h) => strtolower(trim($h)), $header);
            //         $importedCount = 0;
            //         while (($row = fgetcsv($file)) !== false) {
            //             // Skip empty rows
            //             if (empty(array_filter($row))) {
            //                 continue;
            //             }
            //             // Combine header with current row data
            //             $rowData = array_combine($header, $row);
            //             $title = Str::upper(trim($rowData['title'] ?? ''));
            //             $details = Str::upper(trim($rowData['details'] ?? ''));
            //             if (! empty($title)) {
            //                 // 3. Create or Update Skill model entry
            //                 Skill::updateOrCreate(
            //                     ['title' => $title],
            //                     [
            //                         'details' => $details,
            //                         'status' => true, // default status to active
            //                     ]
            //                 );
            //                 $importedCount++;
            //             }
            //         }
            //         fclose($file);
            //         // Optional: Clean up uploaded temp file
            //         Storage::disk('public')->delete($data['dat_file']);

            //         // Log User Activity
            //         ActivityLog::create([
            //             'user_id'   => Auth::id() ?? 'System',
            //             'activity'  => "Uploaded CSV file to import/update {$importedCount} skills",
            //             'module'    => 'Skill Management',
            //             'ipaddress' => request()->ip(),
            //             'windows'   => request()->userAgent(),
            //         ]);
            //         // 4. Send success notification using Filament Notification
            //         Notification::make()
            //             ->title('CSV Import Completed')
            //             ->body("Successfully imported {$importedCount} skills.")
            //             ->success()
            //             ->send();
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
