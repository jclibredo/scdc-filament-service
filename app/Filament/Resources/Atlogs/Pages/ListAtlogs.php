<?php

namespace App\Filament\Resources\Atlogs\Pages;

use App\Filament\Resources\Atlogs\AtlogResource;
use App\Models\Atlog;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ListAtlogs extends ListRecords
{
    protected static string $resource = AtlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // Action::make('importAtlog')
            //     ->label('Import .DAT File')
            //     ->icon('heroicon-o-arrow-up-tray')
            //     ->color('success')
            //     ->form([
            //         FileUpload::make('attlog_file')
            //             ->label('Log File')
            //             ->required()
            //             ->storeFiles(true)
            //             ->disk('local') // Change this to 'local'
            //             ->directory('imports')
            //             ->rules(['file', 'extensions:dat,txt'])
            //     ])
            //     ->action(function (array $data) {
            //         $disk = 'local';

            //         // 1. Verify file exists on the local disk
            //         if (!Storage::disk($disk)->exists($data['attlog_file'])) {
            //             Notification::make()
            //                 ->title('Error')
            //                 ->body('File not found in storage/app/imports')
            //                 ->danger()
            //                 ->send();
            //             return;
            //         }

            //         // 2. Get the full path
            //         $filePath = Storage::disk($disk)->path($data['attlog_file']);

            //         $handle = fopen($filePath, 'r');
            //         $batch = [];
            //         $count = 0;

            //         DB::transaction(function () use ($handle, &$batch, &$count) {
            //             while (($line = fgets($handle)) !== false) {
            //                 // Biometric files are Tab-separated (\t)
            //                 $parts = explode("\t", trim($line));

            //                 if (count($parts) >= 2) {
            //                     $batch[] = [
            //                         'user_id'           => trim($parts[0]),
            //                         'recorded_at'       => Carbon::parse($parts[1]),
            //                         'status'            => $parts[2] ?? 0,
            //                         'verification_mode' => $parts[3] ?? 0,
            //                         'work_code'         => $parts[4] ?? 0,
            //                         'reserved'          => $parts[5] ?? 0,
            //                         'created_at'        => now(),
            //                         'updated_at'        => now(),
            //                     ];
            //                     $count++;
            //                 }

            //                 if (count($batch) >= 500) {
            //                     Atlog::insert($batch);
            //                     $batch = [];
            //                 }
            //             }

            //             if (!empty($batch)) {
            //                 Atlog::insert($batch);
            //             }
            //         });

            //         fclose($handle);

            //         // 3. Cleanup: Delete from storage/app/imports
            //         Storage::disk($disk)->delete($data['attlog_file']);

            //         Notification::make()
            //             ->title('Import Successful')
            //             ->body("Processed {$count} records.")
            //             ->success()
            //             ->send();
            //     }),
            Action::make('importAtlog')
                ->label('Import .DAT File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('attlog_file')
                        ->label('Biometric Log File')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->storeFiles(true)
                        ->rules(['file', 'extensions:dat,txt'])
                ])
                ->action(function (array $data) {
                    $disk = 'local';
                    $filePath = Storage::disk($disk)->path($data['attlog_file']);

                    if (!file_exists($filePath)) {
                        Notification::make()->title('File Error')->danger()->send();
                        return;
                    }

                    $handle = fopen($filePath, 'r');
                    $batch = [];
                    $count = 0;

                    DB::transaction(function () use ($handle, &$batch, &$count) {
                        while (($line = fgets($handle)) !== false) {
                            // Split by tab character
                            $parts = explode("\t", trim($line));

                            if (count($parts) >= 2) {
                                $batch[] = [
                                    'user_id'           => trim($parts[0]), // 49
                                    'recorded_at'       => Carbon::parse($parts[1]), // 2025-10-01 10:12:53
                                    'status'            => (int) ($parts[2] ?? 0),   // 1
                                    'verification_mode' => (int) ($parts[3] ?? 0),   // 0
                                    'work_code'         => (int) ($parts[4] ?? 0),   // 1
                                    'reserved'          => (int) ($parts[5] ?? 0),   // 0
                                    'created_at'        => now(),
                                    'updated_at'        => now(),
                                ];
                                $count++;
                            }

                            if (count($batch) >= 500) {
                                Atlog::insert($batch);
                                $batch = [];
                            }
                        }

                        if (!empty($batch)) {
                            Atlog::insert($batch);
                        }
                    });

                    fclose($handle);
                    Storage::disk($disk)->delete($data['attlog_file']);

                    Notification::make()
                        ->title('Import Successful')
                        ->body("Imported {$count} records.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
