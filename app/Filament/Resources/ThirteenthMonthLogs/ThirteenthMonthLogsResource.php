<?php

namespace App\Filament\Resources\ThirteenthMonthLogs;

use App\Filament\Resources\ThirteenthMonthLogs\Pages\ListThirteenthMonthLogs;
use App\Models\Adjustment;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\GovDeductionLog;
use App\Models\ThirteenthMonth;
use App\Models\YearEndReport;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

// use Illuminate\Support\HtmlString;

class ThirteenthMonthLogsResource extends Resource
{
    protected static ?string $model = ThirteenthMonth::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ThirteenthMonth';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Date Period Configuration')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Manage employee type groups, payroll categories, and active timelines.')
                    ->columns(2) // Aligns fields nicely in a 2-column grid layout
                    ->columnSpanFull()
                    ->schema([
                        // 2. Employee ID
                        Select::make('employeeid')
                            ->label('Employee')
                            ->options(function () {
                                return Employee::query()
                                    ->get()
                                    ->mapWithKeys(function ($employee) {
                                        $fullName = collect([$employee->firstname, $employee->middlename, $employee->lastname])
                                            ->filter()
                                            ->implode(' ');
                                        return [
                                            $employee->employeeid => "{$fullName} [{$employee->employeeid}]"
                                        ];
                                    });
                            })
                            ->default(fn() => session('session_empployeeid'))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        // 7. Allowance (Decimal)
                        TextInput::make('allowance')
                            ->label('Allowance')
                            ->numeric()
                            ->minValue(0) // Blocks negative numbers natively
                            ->placeholder('0.00')
                            ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/', 'min:0'])
                            ->required(),

                        // 8. Earnings (Renamed from total_amount)
                        TextInput::make('earnings')
                            ->label('Earnings')
                            ->numeric()
                            ->minValue(0) // Blocks negative numbers natively
                            ->placeholder('0.00')
                            ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/', 'min:0'])
                            ->required(),

                        // 9. Date Start (maps to model 'datestart', nullable)
                        DatePicker::make('datestart')
                            ->label('Date Start')
                            // ->disabled(function (Get $get) {
                            //     // 🔒 Disables this field if 'periodid' has a value filled in
                            //     return filled($get('periodid'));
                            // })
                            ->dehydrated()
                            ->live()
                            // ->rules([
                            //     function (Get $get) {
                            //         return function (string $attribute, $value, $fail) {
                            //             $yearEndCode = session('session_yearendreportspid');
                            //             if (!$yearEndCode || !$value) {
                            //                 return;
                            //             }
                            //             // Fetch target report boundary limits
                            //             $report = YearEndReport::where('code', $yearEndCode)->first();

                            //             if ($report && $report->datefrom && $report->dateto) {
                            //                 $chosenDate = Carbon::parse($value);
                            //                 $fromBound = Carbon::parse($report->datefrom);
                            //                 $toBound = Carbon::parse($report->dateto);

                            //                 // Check if date falls outside the session report window
                            //                 if (! $chosenDate->between($fromBound, $toBound)) {
                            //                     $fail("The Date Start must be between {$fromBound->format('Y-m-d')} and {$toBound->format('Y-m-d')}.");
                            //                 }
                            //             }
                            //         };
                            //     },
                            // ])
                            ->minDate(function () {
                                $yearEndCode = session('session_yearendreportspid');
                                $report = YearEndReport::where('code', $yearEndCode)->first();
                                return $report?->datefrom ? \Carbon\Carbon::parse($report->datefrom) : null;
                            })
                            ->maxDate(function () {
                                $yearEndCode = session('session_yearendreportspid');
                                $report = YearEndReport::where('code', $yearEndCode)->first();
                                return $report?->dateto ? \Carbon\Carbon::parse($report->dateto) : null;
                            })
                            ->rules([
                                function (Get $get, $record) {
                                    return function (string $attribute, $value, $fail) use ($get, $record) {
                                        $yearEndCode = session('session_yearendreportspid');
                                        $employeeId = session('session_empployeeid');

                                        if (!$yearEndCode || !$value) {
                                            return;
                                        }

                                        // 1. Duplicate & Overlap Check
                                        $duplicateQuery = \App\Models\ThirteenthMonth::where('employeeid', $employeeId)
                                            ->where('yearendrepid', $yearEndCode)
                                            ->where(function ($query) use ($value) {
                                                $query->whereDate('datestart', $value)
                                                    ->orWhereDate('dateend', $value);
                                            });

                                        if ($record) {
                                            $duplicateQuery->where('id', '!=', $record->id);
                                        }

                                        if ($duplicateQuery->exists()) {
                                            $fail("A 13th-month record already exists for this employee within this period configuration with that date.");
                                        }
                                    };
                                },
                            ])
                            ->nullable(),

                        // 10. Date End (maps to model 'dateend', nullable)
                        DatePicker::make('dateend')
                            ->label('Date End')
                            ->after('datestart')
                            ->dehydrated()
                            ->nullable()
                            ->disabled(fn(Get $get) => empty($get('datestart')))
                            ->rules([
                                // Optional: Ensures Date End is required if Date Start has been filled manually
                                fn(Get $get): array => [
                                    'required_with:datestart'
                                ],
                            ]),
                        Hidden::make('yearendrepid')
                            ->default(session('session_yearendreportspid')),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $yearendid     = session('session_yearendreportspid');
        $sessionEmpId = session('session_empployeeid');
        $rep_type     = session('session_reptype');

        $formattedBadges = '';
        $empFullname = '';
        if ($yearendid || $sessionEmpId) {
            $datePerioDetails = cache()->remember(
                "header_admission_full_{$yearendid}",
                3600,
                function () use ($yearendid) {
                    return YearEndReport::where('code', $yearendid)
                        ->where('status', true)
                        ->first();
                }
            );

            $patientDetails = Employee::where('employeeid', $sessionEmpId)
                ->where('status', true)->first();
            if ($patientDetails) {
                $nameParts = array_filter([
                    strtoupper($patientDetails->lastname) ?? null,
                    strtoupper($patientDetails->firstname) ?? null,
                    strtoupper($patientDetails->middlename) ?? null
                ]);
                $empFullname = implode(' ', $nameParts);
            } else {
                $empFullname = 'NO ACTIVE EMPLOYEE SELECTED';
            }
            $emtype = $datePerioDetails?->employeeTypeCategory?->name ?? 'N/A';
            $emstat = $datePerioDetails?->category?->name ?? 'N/A';
            $startdate = $datePerioDetails->datefrom
                ? Carbon::parse($datePerioDetails->datefrom)->format('M d, Y') : 'N/A';
            $enddate = $datePerioDetails->dateto
                ? Carbon::parse($datePerioDetails->dateto)->format('M d, Y') : 'N/A';
            $details = [
                "REPORT TYPE: {$rep_type}",
                "DATE COVERED: {$startdate} - {$enddate}",
                "EMP TYPE: {$emtype}",
                "EMP STATUS: {$emstat}",
            ];
            $formattedBadges = collect($details)
                ->map(fn($detail) => "
                    <span style='
                        padding: 0.25rem 0.625rem; 
                        font-size: 0.75rem; 
                        font-weight: 600; 
                        background-color: #ffffff; 
                        color: #374151; 
                        border-radius: 0.375rem; 
                        border: 1px solid #e5e7eb; 
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); 
                        white-space: nowrap;
                        font-family: system-ui, sans-serif;
                    '>{$detail}</span>
                ")
                ->implode(' ');
        }
        return $table
            ->header(function () use ($yearendid, $sessionEmpId, $empFullname, $formattedBadges) {
                if (blank(session('session_empployeeid'))) {
                    return null;
                }

                // 1. Fetch breakdown records
                $adjustments = \App\Models\Adjustment::with('adjustmentName')
                    ->where('date_period_id', $yearendid)
                    ->where('employee_id', $sessionEmpId)->get();

                $govDeductions = \App\Models\GovDeductionLog::with('govDeduction')
                    ->where('date_period_id', $yearendid)
                    ->where('employee_id', $sessionEmpId)->get();

                $otherDeductions = \App\Models\OtherDeductionLog::with('otherDeduction')
                    ->where('date_period_id', $yearendid)
                    ->where('employee_id', $sessionEmpId)->get();

                // 2. Compute the 13th Month Total Base (Sum of Earnings + Allowances from the main query, divided by 12)
                $thirteenthMonthRecords = \App\Models\ThirteenthMonth::where('yearendrepid', $yearendid)->get();
                $totalEarningsAndAllowances = $thirteenthMonthRecords->sum('earnings') + $thirteenthMonthRecords->sum('allowance');
                $total13thMonth = $totalEarningsAndAllowances / 12;

                // 3. Return everything safely to the clean Blade view wrapper
                return view('filament.table-footer', [
                    'empFullname' => $empFullname,
                    'yearendid' => $yearendid,
                    'formattedBadges' => $formattedBadges,
                    'adjustments' => $adjustments,
                    'govDeductions' => $govDeductions,
                    'otherDeductions' => $otherDeductions,
                    'total13thMonth' => $total13thMonth,
                ]);
            })
            ->recordUrl(null)
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            // 1. Scopes the table data to match the process session if one exists
            ->query(function () {
                $query = ThirteenthMonth::query();
                $yearEndRepCode = session('session_yearendreportspid');
                if ($yearEndRepCode) {
                    // Assuming yearendrepid stores the code from the year-end configuration
                    $query->where('yearendrepid', $yearEndRepCode);
                }
                return $query->orderBy('datestart');
            })
            ->columns([
                TextColumn::make('datestart')
                    ->extraAttributes(['style' => 'font-size: 0.75rem;'])
                    ->label('Date Covered')
                    ->formatStateUsing(function ($record) {
                        // Handle cases where dates might be null
                        if (!$record->datestart || !$record->dateend) {
                            return 'N/A';
                        }
                        // Format both dates in your preferred format (e.g., Jan 01, 2026)
                        $start = $record->datestart->format('M d, Y');
                        $end = $record->dateend->format('M d, Y');
                        return "{$start} - {$end}";
                    })
                    ->sortable(['datestart']),
                // Earnings (Formatted as currency)
                TextColumn::make('earnings')
                    ->extraAttributes(['style' => 'font-size: 0.75rem;'])
                    ->label('Earnings')
                    ->money('PHP')
                    ->sortable(),

                // Allowance (Formatted as currency)
                TextColumn::make('allowance')
                    ->extraAttributes(['style' => 'font-size: 0.75rem;'])
                    ->label('Allowance')
                    ->money('PHP')
                    ->sortable(),
                // ->alignRight(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    // ViewAction::make(),
                    DeleteAction::make()
                        ->label('Remove'),
                    EditAction::make()
                        ->label('Update'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->outlined(),
            ]);
        // ->bulkActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ]);
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
            'index' => ListThirteenthMonthLogs::route('/'),
        ];
    }
}
