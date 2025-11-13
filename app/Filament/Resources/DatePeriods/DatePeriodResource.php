<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\CreateDatePeriod;
use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\ThirteenthMonth;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use UnitEnum;

class DatePeriodResource extends Resource
{
    protected static ?string $model = DatePeriod::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Printer;

    protected static ?string $recordTitleAttribute = 'DatePeriod';

    public static function form(Schema $schema): Schema
    {
        // return DatePeriodForm::configure($schema);
        return $schema
            ->schema([
                Select::make('employeetype')
                    ->label('Employee Type')
                    ->options([
                        'SM' => 'Semi-monthly',
                        'W' => 'Weekly',
                    ])
                    ->required(),

                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                DatePicker::make('datefrom')->label('Date From')->required(),
                DatePicker::make('dateto')->label('Date To')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employeetype')
                    ->label('Employee Type')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'SM' => 'Semi Monthly',
                            'W'  => 'Weekly',
                            default => $state,
                        };
                    }),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('datefrom')->date()->label('Date From'),
                TextColumn::make('dateto')->date()->label('Date To'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_payslip')
                    ->label('View Payslip')
                    ->color('primary')
                    ->button()
                    ->url(fn($record) => route('payslips.view', $record->id))
                    ->openUrlInNewTab(),
                Action::make('upload_data')
                    ->label('Upload Data')
                    ->button()
                    ->form([
                        FileUpload::make('uploadfile')
                            ->label('Upload CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv'])
                            ->disk('public')
                            ->directory('uploads/csv'),
                    ])
                    ->action(function (array $data, $record) {
                        $filePath = storage_path('app/public/' . $data['uploadfile']);
                        $csv = Reader::createFromPath($filePath, 'r');
                        $csv->setHeaderOffset(0);
                        $records = $csv->getRecords(); // iterable
                        foreach ($records as $row) {
                            // Map CSV columns to your ThirteenthMonth fields
                            ThirteenthMonth::create([
                                'periodid' => $record->id,                    // current DatePeriod record
                                'employeeid' => $row['EmployeeID'],          // from CSV
                                'total_amount' => $row['TotalAmount'],       // from CSV
                            ]);
                        }
                        // ✅ Delete the uploaded CSV file
                        Storage::disk('public')->delete($data['uploadfile']);
                        Notification::make()
                            ->title('CSV Uploaded Successfully')
                            ->body("File for DatePeriod #{$record->id} imported successfully.")
                            ->success()
                            ->send();
                    }),

                Action::make('export_csv')
                    ->label('Download Template')
                    ->color('success')
                    ->button()
                    ->action(function ($record) {
                        $emptype = $record->employeetype;
                        // 🧩 Filter employees by selected employee type in active project histories
                        $employees = Employee::whereHas('projectHistories', function ($query) use ($emptype) {
                            $query->where('employeetype', $emptype);
                        })
                            ->with('projectHistories')
                            ->orderBy('lastname', 'asc') // eager load histories
                            ->get();

                        // 🧩 Check if there are any employees
                        if ($employees->isEmpty()) {
                            Notification::make()
                                ->title('No Data Found')
                                ->body('There are no employees to export.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $filename = 'employees_export_' . now()->format('Y_m_d_His') . '.csv';
                        $path = storage_path('app/' . $filename);

                        $handle = fopen($path, 'w');
                        fputcsv($handle, ['EmployeeID', 'LastName', 'FirstName', 'MiddleName', 'Project', 'EmployeeType', 'TotalAmount']); // headers
                        foreach ($employees as $employee) {
                            $activeHistory = $employee->projectHistories->first();
                            fputcsv($handle, [
                                $employee->employeeid,
                                $employee->lastname,
                                $employee->firstname,
                                $employee->middlename,
                                $employee->project_id,
                                $activeHistory?->employeetype,
                                'Enter Amount Here',
                            ]);
                        }
                        fclose($handle);
                        // ✅ Success notification
                        Notification::make()
                            ->title('Export Successful')
                            ->body('The employee data has been exported successfully.')
                            ->success()
                            ->send();

                        // ✅ Return the CSV file for download
                        return response()->download($path)->deleteFileAfterSend(true);
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDatePeriods::route('/'),
            'create' => CreateDatePeriod::route('/create'),
            // 'edit' => EditDatePeriod::route('/{record}/edit'),
        ];
    }
}