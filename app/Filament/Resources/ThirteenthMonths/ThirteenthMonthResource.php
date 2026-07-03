<?php

namespace App\Filament\Resources\ThirteenthMonths;

use App\Filament\Resources\ThirteenthMonthLogs\ThirteenthMonthLogsResource;
use App\Filament\Resources\ThirteenthMonths\Pages\CreateThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\EditThirteenthMonth;
use App\Filament\Resources\ThirteenthMonths\Pages\ListThirteenthMonths;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\ThirteenthMonth;
use App\Models\YearEndReport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ThirteenthMonthResource extends Resource
{
    protected static ?string $model = ThirteenthMonth::class;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::Printer;
    // protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    // protected static ?string $recordTitleAttribute = 'ThirteenthMonth';
    // protected static ?string $navigationLabel = 'Year End Reports Logs';
    protected static ?string $modelLabel = '13Month and Incentives Reports';
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // public static function form(Schema $schema): Schema
    // {
    //     return $schema
    //         ->schema([
    //             Section::make('Date Period Configuration')
    //                 ->extraAttributes([
    //                     'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
    //                 ])
    //                 ->description('Manage employee type groups, payroll categories, and active timelines.')
    //                 ->columns(2) // Aligns fields nicely in a 2-column grid layout
    //                 ->columnSpanFull()
    //                 ->schema([
    //                     // 1. Period ID (Nullable as per migration)
    //                     Select::make('periodid')
    //                         ->label('Period')
    //                         ->options(function () {
    //                             return DatePeriod::query()
    //                                 ->get()
    //                                 ->mapWithKeys(function ($period) {
    //                                     // Formatting dates safely if they are carbon instances or raw strings
    //                                     $from = is_string($period->datefrom) ? date('Y-m-d', strtotime($period->datefrom)) : $period->datefrom?->format('Y-m-d');
    //                                     $to = is_string($period->dateto) ? date('Y-m-d', strtotime($period->dateto)) : $period->dateto?->format('Y-m-d');

    //                                     return [
    //                                         $period->code => "{$from} to {$to} [{$period->code}]"
    //                                     ];
    //                                 });
    //                         })
    //                         ->searchable()
    //                         ->preload()
    //                         ->nullable(),

    //                     // 2. Employee ID
    //                     Select::make('employeeid')
    //                         ->label('Employee')
    //                         ->options(function () {
    //                             return Employee::query()
    //                                 ->get()
    //                                 ->mapWithKeys(function ($employee) {
    //                                     // Cleans up trailing/empty spaces if middlename is missing
    //                                     $fullName = collect([$employee->firstname, $employee->middlename, $employee->lastname])
    //                                         ->filter()
    //                                         ->implode(' ');
    //                                     return [
    //                                         $employee->employeeid => "{$fullName} [{$employee->employeeid}]"
    //                                     ];
    //                                 });
    //                         })
    //                         ->searchable()
    //                         ->preload()
    //                         ->required(),
    //                     // 6. Project
    //                     TextInput::make('project')
    //                         ->label('Project / Cost Center')
    //                         ->required(),

    //                     // 7. Allowance (Decimal)
    //                     TextInput::make('allowance')
    //                         ->label('Allowance')
    //                         ->numeric()
    //                         ->placeholder('0.00')
    //                         ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/'])
    //                         ->required(),

    //                     // 8. Earnings (Renamed from total_amount)
    //                     TextInput::make('earnings')
    //                         ->label('Earnings')
    //                         ->numeric()
    //                         ->placeholder('0.00')
    //                         ->rules(['regex:/^\d{1,10}(\.\d{1,2})?$/'])
    //                         ->required(),

    //                     // 9. Date Start (maps to model 'datestart', nullable)
    //                     DatePicker::make('datestart')
    //                         ->label('Date Start')
    //                         ->nullable(),

    //                     // 10. Date End (maps to model 'dateend', nullable)
    //                     DatePicker::make('dateend')
    //                         ->label('Date End')
    //                         ->nullable(),

    //                 ]),
    //         ]);
    // }


    public static function table(Table $table): Table
    {

        $yearendid     = session('session_yearendreportspid');
        // $sessionpartners  = session('session_partnersid');
        $sessionType    = session('session_employeetypeid');
        $sessionStatus  = session('session_employeestatusid');
        // $sessionproject    = session('session_projectid');
        $sessionReptype  = session('session_reptype');
        if (!$yearendid || !$sessionStatus || !$sessionType) {
            return $table;
        }
        $yearenddetails = cache()->remember(
            "header_admission_full_{$yearendid}",
            3600,
            function () use ($yearendid) {
                return YearEndReport::where('code', $yearendid)
                    ->where('status', true)
                    ->first();
            }
        );
        $emtype = $yearenddetails?->employeeTypeCategory?->name ?? 'N/A';
        $emstat = $yearenddetails?->category?->name ?? 'N/A';
        $startdate = $yearenddetails->datefrom
            ? Carbon::parse($yearenddetails->datefrom)->format('M d, Y') : 'N/A';
        $enddate = $yearenddetails->dateto
            ? Carbon::parse($yearenddetails->dateto)->format('M d, Y') : 'N/A';

        $partnerSession = session('session_partnersid');
        $reportsType = ($sessionReptype === '13THMONTH' ? '13th Month Reports' : 'Processing Reports');
        $details = [
            "DATE START: {$startdate}",
            "DATE END: {$enddate}",
            "EMP TYPE: {$emtype}",
            "EMP STATUS: {$emstat}",
            "REPORT TYPE: {$reportsType}",
        ];
        if ($partnerSession === 'ALL') {
            $details[] = "SUBCON NAME : ALL";
        } elseif ($partnerSession !== '0' && !empty($partnerSession)) {
            $subconName = Category::where('id', $partnerSession)->value('name');
            $details[] = "SUBCON NAME : " . ($subconName ? strtoupper($subconName) : 'N/A');
        }
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
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->header(fn() => new HtmlString("
                    <div style='
                        padding: 1rem; 
                        margin: 1rem 1rem 0 1rem; 
                        border-left: 4px solid #d97706; 
                        background-color: rgba(241, 201, 71, 0.4); 
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
                                    YEAR END REPORT CODE : <span style='font-family: monospace; color: #b45309;'>{$yearendid}</span>
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
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Employee::whereRaw('1 = 0');
                }
                // 1. Fetch the active date period
                $datePeriodData = YearEndReport::where('code', session('session_yearendreportspid'))
                    ->where('status', true)
                    ->first();
                if (! $datePeriodData) {
                    return Employee::query()->whereRaw('1 = 0');
                }
                $query = Employee::query()
                    ->where('status', true)
                    ->where('datehired', '<=', $datePeriodData->datefrom);

                if (! session('session_employeestatusid') || ! session('session_employeetypeid')) {
                    return $query;
                }
                $query->where('empstatus', session('session_employeestatusid'))
                    ->where('employeetype', session('session_employeetypeid'));
                $partnerSession = session('session_partnersid');
                if ($partnerSession && $partnerSession !== 'ALL') {
                    $query->where('partners', $partnerSession);
                }
                $sessionProject = session('session_projectid');
                if ($sessionProject !== null) {
                    $query->where('project_id', $sessionProject);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('lastname', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('lastname', $direction)
                            ->orderBy('firstname', $direction);
                    })
                    ->formatStateUsing(function ($record) {
                        return "{$record->lastname}, {$record->firstname} {$record->middlename}";
                    }),
                TextColumn::make('empType.name')->sortable()->searchable(),
                TextColumn::make('empStat.name')
                    ->label('Emp. Status')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('skill.title')->label('Skill'),
                TextColumn::make('project.name')->label('Project'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                    Action::make('proceedToPayroll')
                        ->label('Process')
                        ->color('warning')
                        ->icon('heroicon-m-arrow-right-circle')
                        ->action(function (Employee $record) {
                            $yearendid     = session('session_yearendreportspid');
                            $partners     = session('session_partnersid');
                            $emptype     = session('session_employeetypeid');
                            $empstatus     = session('session_employeestatusid');
                            $projectid     = session('session_projectid');
                            $rep_type     = session('session_reptype');

                            session([
                                'session_yearendreportspid' => $yearendid,
                                'session_partnersid'        => $partners,
                                'session_employeetypeid'    => $emptype,
                                'session_employeestatusid'  => $empstatus,
                                'session_projectid'         => $projectid,
                                'session_reptype'           => $rep_type,
                                'session_empployeeid'       => $record->employeeid,
                            ]);
                            return redirect(ThirteenthMonthLogsResource::getUrl('index'));
                        }),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->button()
                    ->size('xs')
                    ->outlined(),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('processYearEndReport')
                        ->label('Process Reports')
                        ->color('warning')
                        ->icon('heroicon-m-cog')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $reptypes = session('session_reptype');
                            Notification::make()
                                ->title($reptypes === '13THMONTH' ? 'Processing 13th Month Reports...' : 'Processing Incentives Reports...')
                                ->body('Selected records have been processed.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('printPayslip')
                        ->label('Print Payslip')
                        ->icon('heroicon-o-printer')
                        ->requiresConfirmation()
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $employeeId = $record->employee_id;
                                $periodId   = $record->period_id;

                                // Example: send to worker that prints PDF
                                // PrintPayslipJob::dispatch($employeeId, $periodId);
                            }

                            Notification::make()
                                ->title('Payslips are being generated')
                                ->success()
                                ->send();
                        }),
                ])
                    ->button()
                    ->outlined()
                    ->icon('heroicon-m-chart-bar')
                    ->color('success')
                    ->label(' Actions'),

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
            'index' => ListThirteenthMonths::route('/'),
            'create' => CreateThirteenthMonth::route('/create'),
            'edit' => EditThirteenthMonth::route('/{record}/edit'),
        ];
    }
}
