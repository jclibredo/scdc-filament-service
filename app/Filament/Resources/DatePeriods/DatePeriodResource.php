<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\CreateDatePeriod;
use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
                Section::make('Date Period Configuration')
                    ->description('Manage employee type groups, payroll categories, and active timelines.')
                    ->columns(2) // Aligns fields nicely in a 2-column grid layout
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => strtoupper(Str::random(6)))
                            // 💡 Filament automatically ignores the current record when updating
                            ->unique(table: 'date_periods', column: 'code', ignoreRecord: true)
                            ->required(),
                        Select::make('employeetype')
                            ->label('Employee Type')
                            ->options(function () {
                                // Dynamically filters categories matching the 'EMPLOYEE_TYPE' handle
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_TYPE')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                // Dynamically filters categories matching the 'PAYROLL' handle
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_STATUS')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('datefrom')
                            ->label('Date From')
                            ->required(),

                        DatePicker::make('dateto')
                            ->label('Date To')
                            ->required(),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)    // Disables the URL redirect on click
            ->recordAction(null)
            ->columns([
                TextColumn::make('code')
                    ->label('Control Code')
                    ->searchable()
                    ->placeholder('N/A'),

                // 1. Automatically pulls the Category name through your relationship
                TextColumn::make('employeeTypeCategory.name')
                    ->label('Employee Type')
                    ->sortable()
                    ->searchable(),

                // 2. Your existing category relationship
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('datefrom')
                    ->date('M d, Y') // Nicely formatted (e.g., Jan 15, 2026)
                    ->label('Date From')
                    ->sortable(),

                TextColumn::make('dateto')
                    ->date('M d, Y')
                    ->label('Date To')
                    ->sortable(),
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
                ActionGroup::make([

                    Action::make('proceedToPayroll')
                        ->label('Go to Payroll')
                        ->icon('heroicon-m-arrow-right-circle')
                        ->color('success')
                        ->action(function (DatePeriod $record) {
                            // 1. Set the exact session keys required by PayrollResource query
                            session(['session_employeetype' => $record->employeetype]);

                            // Adjust this if your employee status relies on another field 
                            // e.g., if date period implies a status, or if you use its category ID
                            session(['session_employeestatus' => $record->category_id]); // Example fallback, or map dynamically

                            // 2. Redirect straight to the PayrollResource Index page
                            return redirect(PayrollResource::getUrl('index'));
                        }),

                    EditAction::make()
                        ->label('Update'),
                    // DeleteAction::make()
                    //     ->label('Remove')
                    //     ->before(function ($record) {
                    //         DB::table('thirteenth_months')
                    //             ->where('periodid', $record->id)
                    //             ->delete();

                    //         DB::table('gov_deduction_logs')
                    //             ->where('date_period_id', $record->id)
                    //             ->delete();

                    //         DB::table('other_deduction_logs')
                    //             ->where('date_period_id', $record->id)
                    //             ->delete();
                    //     }),

                    // Action::make('clean_data')
                    //     ->label('Clean Data')
                    //     ->color('success')
                    //     ->visible(function ($record) {
                    //         // 1. Check if category is 'REGULARPAYROLL'
                    //         $isRegularPayroll = $record->category?->name === 'REGULARPAYROLL';

                    //         // 2. Check if data already exists in the table
                    //         $dataExists = DB::table('thirteenth_months')
                    //             ->where('periodid', $record->id)
                    //             ->exists();

                    //         // Visible ONLY if it is Regular Payroll AND data does not exist yet
                    //         return !$isRegularPayroll &&  $dataExists;
                    //     })
                    //     ->icon('heroicon-o-trash')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Clean Thirteenth Month Data')
                    //     ->modalSubheading('This will delete all 13th month data associated with this period. This action cannot be undone.')
                    //     ->modalButton('Yes, delete')
                    //     ->action(function ($record) {
                    //         // Direct DB delete queries
                    //         DB::table('thirteenth_months')
                    //             ->where('periodid', $record->id)
                    //             ->delete();

                    //         DB::table('gov_deduction_logs')
                    //             ->where('date_period_id', $record->id)
                    //             ->delete();

                    //         DB::table('other_deduction_logs')
                    //             ->where('date_period_id', $record->id)
                    //             ->delete();
                    //         Notification::make()
                    //             ->title('Data Cleaned')
                    //             ->body("All thirteenth month data for Period #{$record->id} has been removed.")
                    //             ->success()
                    //             ->send();
                    //     }),

                    // Action::make('view_payslip')
                    //     ->label('View Payslip')
                    //     ->visible(fn($record) => $record->category?->name !== 'REGULARPAYROLL')
                    //     ->color('primary')
                    //     ->button()
                    //     ->url(fn($record) => route('payslips.view', $record->id))
                    //     ->openUrlInNewTab(),

                    // Action::make('upload_data')
                    //     ->label('Upload Data')
                    //     ->button()
                    //     ->visible(function ($record) {
                    //         // 1. Check if category is 'REGULARPAYROLL'
                    //         $isRegularPayroll = $record->category?->name === 'REGULARPAYROLL';

                    //         // 2. Check if data already exists in the table
                    //         $dataExists = DB::table('thirteenth_months')
                    //             ->where('periodid', $record->id)
                    //             ->exists();

                    //         // Visible ONLY if it is Regular Payroll AND data does not exist yet
                    //         return !$isRegularPayroll && ! $dataExists;
                    //     })
                    //     ->form([
                    //         FileUpload::make('uploadfile')
                    //             ->label('Upload CSV File')
                    //             ->required()
                    //             ->acceptedFileTypes(['text/csv'])
                    //             ->disk('public')
                    //             ->directory('uploads/csv'),
                    //     ])
                    //     ->action(function (array $data, $record) {
                    //         $filePath = storage_path('app/public/' . $data['uploadfile']);
                    //         $csv = Reader::createFromPath($filePath, 'r');
                    //         $csv->setHeaderOffset(0);
                    //         $records = $csv->getRecords(); // iterable
                    //         foreach ($records as $row) {
                    //             // Map CSV columns to your ThirteenthMonth fields
                    //             DB::table('thirteenth_months')->insert([
                    //                 'periodid'      => $record->id,
                    //                 'employeeid'    => $row['EmployeeID'],
                    //                 'total_amount'  => $row['TotalAmount'],
                    //                 'created_at'    => now(),
                    //                 'updated_at'    => now(),
                    //             ]);
                    //         }
                    //         // ✅ Delete the uploaded CSV file
                    //         Storage::disk('public')->delete($data['uploadfile']);
                    //         Notification::make()
                    //             ->title('CSV Uploaded Successfully')
                    //             ->body("File for DatePeriod #{$record->id} imported successfully.")
                    //             ->success()
                    //             ->send();
                    //     }),

                    // Action::make('export_csv')
                    //     ->label('Download Template')
                    //     ->color('success')
                    //     ->button()
                    //     ->visible(function ($record) {
                    //         // 1. Check if category is 'REGULARPAYROLL'
                    //         $isRegularPayroll = $record->category?->name === 'REGULARPAYROLL';

                    //         // 2. Check if data already exists in the table
                    //         $dataExists = DB::table('thirteenth_months')
                    //             ->where('periodid', $record->id)
                    //             ->exists();

                    //         // Visible ONLY if it is Regular Payroll AND data does not exist yet
                    //         return !$isRegularPayroll && ! $dataExists;
                    //     })
                    //     ->action(function ($record) {
                    //         $emptype = $record->employeetype;
                    //         $employees = Employee::query()
                    //             ->where('employeetype', $emptype)   // now filtered directly from Employee table
                    //             ->where('status', 1)                   // still load histories if needed
                    //             ->orderBy('lastname', 'asc')
                    //             ->get();

                    //         // 🧩 Check if there are any employees
                    //         if ($employees->isEmpty()) {
                    //             Notification::make()
                    //                 ->title('No Data Found')
                    //                 ->body('There are no employees to export.')
                    //                 ->warning()
                    //                 ->send();
                    //             return;
                    //         }

                    //         $filename = 'employees_export_' . now()->format('Y_m_d_His') . '.csv';
                    //         $path = storage_path('app/' . $filename);

                    //         $handle = fopen($path, 'w');
                    //         fputcsv($handle, ['EmployeeID', 'LastName', 'FirstName', 'MiddleName', 'Project', 'EmployeeType', 'TotalAmount']); // headers
                    //         foreach ($employees as $employee) {
                    //             // $activeHistory = $employee->projectHistories->first();
                    //             fputcsv($handle, [
                    //                 $employee->employeeid,
                    //                 $employee->lastname,
                    //                 $employee->firstname,
                    //                 $employee->middlename,
                    //                 $employee->project_id,
                    //                 $employee->employeetype,
                    //                 'Enter Amount Here',
                    //             ]);
                    //         }
                    //         fclose($handle);
                    //         // ✅ Success notification
                    //         Notification::make()
                    //             ->title('Export Successful')
                    //             ->body('The employee data has been exported successfully.')
                    //             ->success()
                    //             ->send();

                    //         // ✅ Return the CSV file for download
                    //         return response()->download($path)->deleteFileAfterSend(true);
                    //     }),

                    // //THIS ACTION IS FOR REGULAR PAYROLL PROCESS
                    // Action::make('your_action_name')
                    //     ->label('Process Payroll')
                    //     ->color('success')
                    //     ->button()
                    //     ->visible(function ($record) {
                    //         // Check if the relationship exists and the name matches
                    //         return $record->category && $record->category->name === 'REGULARPAYROLL';
                    //     })
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),


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
