<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\GovDeduction;
use App\Models\GovDeductionLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                    ->columnSpanFull()
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
                            ->live()
                            ->required(),
                        // 2. Dependent Sub. Contractor Field
                        Select::make('partners')
                            ->label('Sub. Contractor')
                            ->options(function () {
                                return Category::query()
                                    ->where('cat', 'SUBCON')
                                    ->pluck('name', 'id')
                                    ->prepend('ALL', 'ALL');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(function (Get $get) {
                                $selectedId = $get('employeetype');
                                if (! $selectedId) {
                                    return false;
                                }
                                $category = Category::find($selectedId);
                                return $category && strtoupper($category->name) === 'SUB-CON';
                            })
                            ->required(
                                fn(Get $get) => ($cat = Category::find($get('employeetype'))) && strtoupper($cat->name) === 'SUB-CON'
                            ),
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

                        TextInput::make('overtime_rate')
                            ->label('Overtime Rate (%)')
                            ->placeholder('e.g., 120.00')
                            ->numeric()
                            ->required()
                            ->default(25.00) // Controls the default for NEW records
                            ->formatStateUsing(fn($state) => $state ?? 20.00) // Controls fallback for EXISTING records
                            ->rules(['regex:/^\d{1,6}(\.\d{1,2})?$/'])
                            ->helperText('Specify the baseline percentage multiplier for overtime calculations (e.g., 120.00 for 120%).'),

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
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return DatePeriod::whereRaw('1 = 0');
                }
                if (
                    session('session_employeestatus')
                    && session('session_employeetype')
                    && session('session_periodcode')
                ) {
                    return DatePeriod::query()
                        ->where('status', true)
                        ->where('code', session('session_periodcode'))
                        ->where('category_id', session('session_employeestatus'))
                        ->where('employeetype', session('session_employeetype'));
                }
                return DatePeriod::query()
                    ->where('status', true);
            })
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

                // 3. New Overtime Rate column
                TextColumn::make('overtime_rate')
                    ->label('OT Rate')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->alignEnd() // Aligns numbers cleanly to the right side of the column
                    ->sortable()
                    ->placeholder('---'), // Displayed if no custom rate is specified (null)

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
                // 4. Filter by Date Range (Date From & Date To combined)
                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('datefrom')
                            ->label('Date From')
                            ->native(false)
                            ->displayFormat('M d, Y'),
                        DatePicker::make('dateto')
                            ->label('Date To')
                            ->native(false)
                            ->displayFormat('M d, Y'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['datefrom'],
                                fn(Builder $query, $date): Builder => $query->whereDate('datefrom', '>=', $date),
                            )
                            ->when(
                                $data['dateto'],
                                fn(Builder $query, $date): Builder => $query->whereDate('dateto', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['datefrom'] ?? null) {
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($data['datefrom'])->toFormattedDateString();
                        }
                        if ($data['dateto'] ?? null) {
                            $indicators[] = 'To: ' . \Carbon\Carbon::parse($data['dateto'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
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
                    ->columns(1)
                    ->placeholder('Select Code'),
                // 1. FILTER: Employee Type (Filtered by Category: EMPLOYEE_TYPE)
                SelectFilter::make('employeetype_id')
                    ->label('Emp. Type')
                    ->relationship(
                        name: 'employeeTypeCategory',
                        titleAttribute: 'name',
                        // 💡 Scopes down the drop-down list to ONLY show items under this category
                        modifyQueryUsing: fn(Builder $query) => $query->where('cat', 'EMPLOYEE_TYPE')
                    )
                    ->preload()
                    ->columns(1)
                    ->placeholder('All Employee Types'),
                // 2. FILTER: Employment Status (Filtered by Category: EMPLOYEE_STATUS)
                SelectFilter::make('empstatus_id')
                    ->label('Emp. Status')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        // 💡 Scopes down the drop-down list to ONLY show items under this category
                        modifyQueryUsing: fn(Builder $query) => $query->where('cat', 'EMPLOYEE_STATUS')
                    )
                    ->preload()
                    ->columns(1)
                    ->placeholder('All Statuses'),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersFormWidth('full')
            ->actions([
                ActionGroup::make([
                    Action::make('proceedToPayroll')
                        ->label('Process')
                        ->color('success')
                        ->icon('heroicon-m-calculator')
                        ->action(function (DatePeriod $record) {
                            session(['session_periodcode' => $record->code]);
                            session(['session_partners' => $record->partners]);
                            session(['session_employeetype' => $record->employeetype]);
                            session(['session_employeestatus' => $record->category_id]);
                            return redirect(PayrollResource::getUrl('index'));
                        }),
                    Action::make('gov_contribution')
                        ->label('Deductables')
                        ->icon('heroicon-m-calculator')
                        ->color('success')
                        ->modalHeading('Manage Government Contributions')
                        ->modalWidth('md')
                        ->form(function ($record) {
                            return [
                                Select::make('gov_deduction_ids')
                                    ->label('Select Contributions')
                                    ->options(
                                        GovDeduction::query()
                                            ->pluck('title', 'id')
                                            ->toArray()
                                    )
                                    ->multiple()
                                    ->statePath('gov_deduction_ids')
                                    ->formatStateUsing(function () use ($record) {
                                        return GovDeductionLog::where('date_period_id', $record->code)
                                            ->distinct()
                                            ->pluck('gov_deduction_id')
                                            ->toArray() ?? [];
                                    })
                                    ->preload()
                                    ->searchable()
                                    ->native(false),
                            ];
                        })
                        ->action(function (array $data, $record) {
                            $selectedDeductionIds = data_get($data, 'gov_deduction_ids', []);
                            // 2. Fetch the target employee IDs
                            $employeeIds = Employee::where('empstatus', $record->category_id)
                                ->where('employeetype', $record->employeetype)
                                ->where('status', true)
                                ->pluck('employeeid')
                                ->toArray();
                            if (empty($employeeIds)) {
                                Notification::make()
                                    ->title('No matching active employees found')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            DB::transaction(function () use ($selectedDeductionIds, $employeeIds, $record) {
                                GovDeductionLog::where('date_period_id', $record->code)
                                    ->whereIn('employee_id', $employeeIds)
                                    ->delete();
                                if (empty($selectedDeductionIds)) {
                                    return;
                                }
                                $insertData = [];
                                $timestamp = now();
                                foreach ($employeeIds as $employeeId) {
                                    foreach ($selectedDeductionIds as $deductionId) {
                                        $insertData[] = [
                                            'gov_deduction_id' => $deductionId,
                                            'employee_id'      => $employeeId,
                                            'date_period_id'   => $record->code,
                                            'created_at'       => $timestamp,
                                            'updated_at'       => $timestamp,
                                        ];
                                    }
                                }

                                // UPDATED: Replaced delete + insert loop with an intelligent native upsert block
                                foreach (array_chunk($insertData, 500) as $chunk) {
                                    GovDeductionLog::upsert(
                                        $chunk,
                                        ['gov_deduction_id', 'employee_id', 'date_period_id'], // 1. Unique keys to check for matching rows
                                        ['updated_at']                                        // 2. What columns to change if a duplicate is found (just touch timestamp, skipping changes to the main structural data)
                                    );
                                }
                            });

                            Notification::make()
                                ->title('Government Contributions Synchronized')
                                ->body('New items were added, while existing data was safely skipped.')
                                ->success()
                                ->send();
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
            // 'create' => CreateDatePeriod::route('/create'),
            // 'edit' => EditDatePeriod::route('/{record}/edit'),
        ];
    }
}
