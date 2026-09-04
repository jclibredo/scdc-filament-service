<?php

namespace App\Filament\Resources\IncentiveBonuses\Pages;

use App\Filament\Resources\IncentiveBonuses\IncentiveBonusResource;
use App\Filament\Resources\YearEndReports\YearEndReportResource;
use App\Http\Controllers\IncentiveBonusCSVController;
use App\Models\IncentiveBonus;
use Filament\Actions\Action;
// use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListIncentiveBonuses extends ListRecords
{
    protected static string $resource = IncentiveBonusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_billing')
                ->label('Back')
                ->button()
                ->color('success')
                ->size('xs')
                ->outlined()
                ->icon('heroicon-m-arrow-left')
                ->action(function () {
                    $code = session('session_yearendreportspid');
                    session()->forget([
                        'session_yearendreportspid',
                        'session_partnersid',
                        'session_employeetypeid',
                        'session_employeestatusid',
                        'session_projectid'
                    ]);
                    session([
                        'session_yearendreportspid' => $code,
                    ]);
                    return redirect()->to(YearEndReportResource::getUrl('index'));
                }),

            // Export Action
            Action::make('exportIncentiveBonusCsv')
                ->label('Export .CSV File')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->size('xs')
                ->outlined()
                ->action(function () {
                    return app(IncentiveBonusCSVController::class)->export(request());
                }),

            // Import Action
            Action::make('importIncentiveBonusCsv')
                ->label('Import .CSV File')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->size('xs')
                ->outlined()
                ->form([
                    FileUpload::make('file')
                        ->label('Upload Edited .CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required()
                        ->preserveFilenames(),
                ])
                ->action(function (array $data) {
                    $yearendid = session('session_yearendreportspid');

                    if (! $yearendid) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Active Year-End Report Period session parameter is missing.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Get relative upload path from Filament form state
                    $relativePath = $data['file'];

                    // Determine disk (checks default disk or fallback)
                    $disk = Storage::disk(config('filament.default_filesystem_disk', 'public'));

                    if (! $disk->exists($relativePath)) {
                        // Fallback check on standard local storage
                        $disk = Storage::disk('local');
                    }

                    if (! $disk->exists($relativePath)) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Uploaded file could not be retrieved.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $filePath = $disk->path($relativePath);
                    $file = fopen($filePath, 'r');
                    $headerFound = false;

                    // Find header row starting with 'employeeid'
                    while (($row = fgetcsv($file, 1000, ',')) !== false) {
                        $firstCol = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim($row[0] ?? ''));
                        if (strtolower($firstCol) === 'employeeid') {
                            $headerFound = true;
                            break;
                        }
                    }

                    if (! $headerFound) {
                        fclose($file);
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Invalid CSV format. Header row starting with "employeeid" was not found.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $updatedCount = 0;
                    $skippedCount = 0;

                    // Process data rows
                    while (($dataRow = fgetcsv($file, 1000, ',')) !== false) {
                        if (count($dataRow) < 6) {
                            continue;
                        }

                        $employeeId = trim($dataRow[0]);
                        $amountRaw  = trim($dataRow[5]); // 'amount' column index

                        if (empty($employeeId)) {
                            continue;
                        }

                        $earnings = (float) str_replace([',', ' '], '', $amountRaw);

                        // Target existing record matching both conditions
                        $record = IncentiveBonus::where('employeeid', $employeeId)
                            ->where('yearendrepid', $yearendid)
                            ->first();

                        if ($record) {
                            $record->update([
                                'status'   => 1,
                                'earnings' => $earnings,
                            ]);

                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }

                    fclose($file);

                    // Cleanup temporary uploaded file
                    $disk->delete($relativePath);

                    Notification::make()
                        ->title('Import Completed')
                        ->body("Successfully updated {$updatedCount} record(s). Skipped {$skippedCount} non-existing record(s).")
                        ->success()
                        ->send();
                }),
        ];
    }
}
