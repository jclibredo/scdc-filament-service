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

class ListSkills extends ListRecords
{
    protected static string $resource = SkillResource::class;

    protected function getHeaderActions(): array
    {
        return [

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
                    // 1. Get uploaded file path from storage
                    $filePath = Storage::disk('public')->path($data['dat_file']);
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
                    // Sanitize headers to lower case (e.g. "Title" -> "title")
                    $header = array_map(fn($h) => strtolower(trim($h)), $header);
                    $importedCount = 0;
                    while (($row = fgetcsv($file)) !== false) {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        // Combine header with current row data
                        $rowData = array_combine($header, $row);
                        $title = Str::upper(trim($rowData['title'] ?? ''));
                        $details = Str::upper(trim($rowData['details'] ?? ''));

                        if (! empty($title)) {
                            // 3. Create or Update Skill model entry
                            Skill::updateOrCreate(
                                ['title' => $title],
                                [
                                    'details' => $details,
                                    'status' => true, // default status to active
                                ]
                            );
                            $importedCount++;
                        }
                    }
                    fclose($file);
                    // Optional: Clean up uploaded temp file
                    Storage::disk('public')->delete($data['dat_file']);

                    // Log User Activity
                    ActivityLog::create([
                        'user_id'   => Auth::id() ?? 'System',
                        'activity'  => "Uploaded CSV file to import/update {$importedCount} skills",
                        'module'    => 'Skill Management',
                        'ipaddress' => request()->ip(),
                        'windows'   => request()->userAgent(),
                    ]);
                    // 4. Send success notification using Filament Notification
                    Notification::make()
                        ->title('CSV Import Completed')
                        ->body("Successfully imported {$importedCount} skills.")
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
