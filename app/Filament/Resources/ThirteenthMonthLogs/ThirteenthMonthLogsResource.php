<?php

namespace App\Filament\Resources\ThirteenthMonthLogs;

use App\Filament\Resources\ThirteenthMonthLogs\Pages\ListThirteenthMonthLogs;
use App\Models\DatePeriod;
use App\Models\Employee;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                        // 1. Period ID (Nullable as per migration)
                        Select::make('periodid')
                            ->label('Period')
                            ->options(function () {
                                $yearEndCode = session('session_yearendreportspid');
                                $partners     = session('session_partnersid');
                                $emptype       = session('session_employeetypeid');
                                $empstatus     = session('session_employeestatusid');
                                $projectid     = session('session_projectid');
                                $query = DatePeriod::query();
                                if ($emptype) {
                                    $query->where('employeetype', $emptype);
                                }
                                if ($empstatus) {
                                    $query->where('category_id', $empstatus);
                                }
                                if ($projectid) {
                                    $query->where('projectid', $projectid);
                                }
                                // Get the active YearEndReport from the session
                                if ($partners !== 'ALL' && $partners !== null) {
                                    $query->where('partners', $partners);
                                }
                                if ($yearEndCode) {
                                    $report = YearEndReport::where('code', $yearEndCode)->first();
                                    // If the report exists, filter periods within its date range
                                    if ($report && $report->datefrom && $report->dateto) {
                                        $query->whereBetween('datefrom', [$report->datefrom, $report->dateto])
                                            ->whereBetween('dateto', [$report->datefrom, $report->dateto]);
                                    }
                                }
                                return $query->get()->mapWithKeys(function ($period) {
                                    $from = is_string($period->datefrom) ? date('Y-m-d', strtotime($period->datefrom)) : $period->datefrom?->format('Y-m-d');
                                    $to = is_string($period->dateto) ? date('Y-m-d', strtotime($period->dateto)) : $period->dateto?->format('Y-m-d');

                                    return [
                                        $period->id => "{$from} to {$to} [{$period->code}]" // Note: changed key to $period->id to match typical relationship foreign keys
                                    ];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),

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
                            ->placeholder('0.00')
                            ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/'])
                            ->required(),

                        // 8. Earnings (Renamed from total_amount)
                        TextInput::make('earnings')
                            ->label('Earnings')
                            ->numeric()
                            ->placeholder('0.00')
                            ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/'])
                            ->required(),

                        // 9. Date Start (maps to model 'datestart', nullable)
                        DatePicker::make('datestart')
                            ->label('Date Start')
                            ->nullable(),

                        // 10. Date End (maps to model 'dateend', nullable)
                        DatePicker::make('dateend')
                            ->label('Date End')
                            ->nullable(),
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
            ->header(fn() => blank(session('session_empployeeid')) ? null : new HtmlString("
                        <div style='
                            padding: 1rem; 
                            margin: 1rem 1rem 0 1rem; 
                            border-left: 4px solid #d97706; 
                            background-color: rgba(254, 243, 199, 0.4); 
                            border-top-right-radius: 0.75rem; 
                            border-bottom-right-radius: 0.75rem; 
                            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        '>
                            <div style='
                                display: flex; 
                                flex-direction: column; 
                                gap: 0.75rem;
                            '>
                                <div style='display: flex; align-items: center; gap: 0.5rem;'>
                                    <span style='
                                        width: 0.5rem; 
                                        height: 0.5rem; 
                                        background-color: #f59e0b; 
                                        border-radius: 9999px;
                                    '></span>
                                    <h3 style='
                                        font-size: 1rem; 
                                        font-weight: 700; 
                                        color: #111827; 
                                        margin: 0;
                                        font-family: system-ui, sans-serif;
                                    '>
                                        {$empFullname} <span style='font-family: monospace; color: #b45309;'>{$yearendid}</span>
                                    </h3>
                                </div>
                                <div style='
                                    display: flex; 
                                    flex-wrap: wrap; 
                                    align-items: center; 
                                    gap: 0.5rem;
                                '>
                                    {$formattedBadges}
                                </div>
                            </div>
                        </div>
                    "))
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
                return $query;
            })
            ->columns([
                // Period Relationship (Assuming DatePeriod has a 'name' or 'code' column)
                TextColumn::make('periodid')
                    ->label('Period Code')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Earnings (Formatted as currency)
                TextColumn::make('earnings')
                    ->label('Earnings')
                    ->money('PHP') // Change currency code if needed
                    ->sortable(),
                // ->alignRight(),

                // Allowance (Formatted as currency)
                TextColumn::make('allowance')
                    ->label('Allowance')
                    ->money('PHP')
                    ->sortable(),
                // ->alignRight(),
                TextColumn::make('datestart')
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
            ])
            ->filters([
                // SelectFilter::make('project')
                //     ->label('Filter by Project')
                //     ->options(fn() => ThirteenthMonth::whereNotNull('project')->distinct()->pluck('project', 'project')),
            ])
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
