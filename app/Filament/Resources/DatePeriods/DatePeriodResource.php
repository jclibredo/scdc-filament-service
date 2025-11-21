<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\CreateDatePeriod;
use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\GovDeductionLog;
use App\Models\OtherDeductionLog;
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
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        return $schema
            ->schema([
                TextInput::make('code')
                    ->label('Code')
                    ->disabled()                // user cannot edit
                    ->dehydrated()              // still save to DB
                    ->default(fn() => strtoupper(Str::random(6)))
                    ->rule(
                        Rule::unique('date_periods', 'code')   // direct DB table check
                    ) // avoid duplicates
                    ->required(),
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
                TextColumn::make('code')
                    ->label('Control Code')
                    ->placeholder('N/A'),
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
            ->filters([
                // Filter by Control Code
                SelectFilter::make('code')
                    ->label('Control Code')
                    ->options(
                        DatePeriod::query()
                            ->whereNotNull('code')
                            ->where('code', '!=', '')
                            ->orderBy('code')
                            ->pluck('code', 'code')
                    )
                    ->placeholder('Select Code'),

                // Filter by Employee Type
                SelectFilter::make('employeetype')
                    ->label('Employee Type')
                    ->options([
                        'SM' => 'Semi Monthly',
                        'W'  => 'Weekly',
                    ])
                    ->placeholder('Select Employee Type'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormWidth('2xl')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record) {
                        DB::table('thirteenth_months')
                            ->where('periodid', $record->id)
                            ->delete();

                        DB::table('gov_deduction_logs')
                            ->where('date_period_id', $record->id)
                            ->delete();

                        DB::table('other_deduction_logs')
                            ->where('date_period_id', $record->id)
                            ->delete();
                    }),

                Action::make('clean_data')
                    ->label('Clean Data')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        DB::table('thirteenth_months')
                            ->where('periodid', $record->id)
                            ->exists()
                    )
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Clean Thirteenth Month Data')
                    ->modalSubheading('This will delete all 13th month data associated with this period. This action cannot be undone.')
                    ->modalButton('Yes, delete')
                    ->action(function ($record) {
                        // Direct DB delete queries
                        DB::table('thirteenth_months')
                            ->where('periodid', $record->id)
                            ->delete();

                        DB::table('gov_deduction_logs')
                            ->where('date_period_id', $record->id)
                            ->delete();

                        DB::table('other_deduction_logs')
                            ->where('date_period_id', $record->id)
                            ->delete();
                        Notification::make()
                            ->title('Data Cleaned')
                            ->body("All thirteenth month data for Period #{$record->id} has been removed.")
                            ->success()
                            ->send();
                    }),

                Action::make('view_payslip')
                    ->label('View Payslip')
                    ->color('primary')
                    ->button()
                    ->url(fn($record) => route('payslips.view', $record->id))
                    ->openUrlInNewTab(),

                Action::make('upload_data')
                    ->label('Upload Data')
                    ->button()
                    ->visible(
                        fn($record) =>

                        ! DB::table('thirteenth_months')
                            ->where('periodid', $record->id)
                            ->exists()
                    )
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
                            DB::table('thirteenth_months')->insert([
                                'periodid'      => $record->id,
                                'employeeid'    => $row['EmployeeID'],
                                'total_amount'  => $row['TotalAmount'],
                                'created_at'    => now(),
                                'updated_at'    => now(),
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
                    ->visible(
                        fn($record) =>
                        ! DB::table('thirteenth_months')
                            ->where('periodid', $record->id)
                            ->exists()
                    )
                    ->action(function ($record) {
                        $emptype = $record->employeetype;
                        // 🧩 Filter employees by selected employee type in active project histories
                        // $employees = Employee::whereHas('projectHistories', function ($query) use ($emptype) {
                        //     $query->where('employeetype', $emptype);
                        // })
                        //     ->with('projectHistories')
                        //     ->orderBy('lastname', 'asc') // eager load histories
                        //     ->get();
                        $employees = Employee::query()
                            ->where('employeetype', $emptype)   // now filtered directly from Employee table
                            ->where('status', 1)                // optional: only active employees
                            // ->with('projectHistories')          // still load histories if needed
                            ->orderBy('lastname', 'asc')
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
                            // $activeHistory = $employee->projectHistories->first();
                            fputcsv($handle, [
                                $employee->employeeid,
                                $employee->lastname,
                                $employee->firstname,
                                $employee->middlename,
                                $employee->project_id,
                                $employee->employeetype,
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
