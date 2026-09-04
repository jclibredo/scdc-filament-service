<?php

namespace App\Filament\Resources\IncentiveBonuses;

use App\Filament\Resources\IncentiveBonuses\Pages\CreateIncentiveBonus;
use App\Filament\Resources\IncentiveBonuses\Pages\EditIncentiveBonus;
use App\Filament\Resources\IncentiveBonuses\Pages\ListIncentiveBonuses;
use App\Models\Adjustment;
use App\Models\Category;
use App\Models\Employee;
use App\Models\GovDeduction;
use App\Models\GovDeductionLog;
use App\Models\IncentiveBonus;
use App\Models\OtherDeduction;
use App\Models\OtherDeductionLog;
use App\Models\YearEndReport;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class IncentiveBonusResource extends Resource
{
    protected static ?string $model = IncentiveBonus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'IncentiveBonus';
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function table(Table $table): Table
    {
        $yearendid     = session('session_yearendreportspid');
        $sessionType    = session('session_employeetypeid');
        $sessionStatus  = session('session_employeestatusid');
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
        $reportsType = ($sessionReptype === '13THMONTH' ? '13th Month Reports' : 'Incentives Reports');
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
                        border-inline-start: 4px solid #d97706; 
                        background-color: rgba(241, 201, 71, 0.4); 
                        border-start-end-radius: 0.75rem; 
                        border-end-end-radius: 0.75rem; 
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                    '>
                        <div style='
                            display: flex; 
                            flex-direction: column; 
                            gap: 0.75rem;
                        '>
                            <div style='display: flex; align-items: center; gap: 0.5rem;'>
                                <span style='
                                    inline-size: 0.5rem; 
                                    block-size: 0.5rem; 
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
                $project = session('session_projectid');
                if ($project && $project !== 'ALL') {
                    $query->where('project_id', $project);
                }
                return $query->orderBy('lastname');
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
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                Action::make('manage_earnings_deductions')
                    ->label('Manage ' . session('session_reptype') ?? 'REPORTS')
                    ->icon('heroicon-m-cog')
                    ->color('success')
                    ->button()
                    ->outlined()
                    ->modalHeading(fn(Employee $record) => "Manage Earnings, Deductions & Adjustments - {$record->lastname}, {$record->firstname}")
                    ->mountUsing(function (Schema $form, Employee $record) {
                        $yearEndId = session('session_yearendreportspid');
                        $employeeId = $record->employeeid;

                        $formData = [
                            'earnings'           => null,
                            'earnings_status'    => true,
                            'otherdeductionData' => [],
                            'govdeductionData'   => [],
                            'adjustmentData'     => [],
                        ];

                        if ($employeeId && $yearEndId) {
                            // Fetch Earnings (Incentive Bonus) record
                            $incentive = IncentiveBonus::query()
                                ->where('yearendrepid', $yearEndId)
                                ->where('employeeid', $employeeId)
                                ->first();

                            if ($incentive) {
                                $formData['earnings'] = (float) $incentive->earnings;
                                $formData['earnings_status'] = (bool) $incentive->status;
                            }

                            // Fetch Other Deductions
                            $formData['otherdeductionData'] = OtherDeductionLog::query()
                                ->where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)
                                ->get(['other_deduction_id', 'amount'])
                                ->map(fn($item) => [
                                    'other_deduction_id' => $item->other_deduction_id,
                                    'amount'             => (float) $item->amount,
                                ])
                                ->toArray();

                            // Fetch Mandated/Gov Deductions
                            $formData['govdeductionData'] = GovDeductionLog::query()
                                ->where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)
                                ->get(['gov_deduction_id', 'amount'])
                                ->map(fn($item) => [
                                    'gov_deduction_id' => $item->gov_deduction_id,
                                    'amount'           => (float) $item->amount,
                                ])
                                ->toArray();

                            // Fetch Adjustments
                            $formData['adjustmentData'] = Adjustment::query()
                                ->where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)
                                ->get(['adjustment_id', 'amount'])
                                ->map(fn($item) => [
                                    'adjustment_id' => $item->adjustment_id,
                                    'amount'        => (float) $item->amount,
                                ])
                                ->toArray();
                        }

                        $form->fill($formData);
                    })
                    ->form([
                        Tabs::make('Earnings, Deductions, and Adjustments')
                            ->extraAttributes([
                                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;',
                            ])
                            ->tabs([
                                Tabs\Tab::make('Earnings')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('earnings')
                                            ->label('Earnings Amount')
                                            ->numeric()
                                            ->prefix('₱')
                                            ->nullable(),
                                        Toggle::make('earnings_status')
                                            ->label('Active Status')
                                            ->disabled()
                                            ->default(true)
                                            ->inline(false),
                                    ]),

                                Tabs\Tab::make('Other Deductions')
                                    ->schema([
                                        Repeater::make('otherdeductionData')
                                            ->defaultItems(0)
                                            ->addAction(fn(Action $action) => $action->color('warning')->outlined())
                                            ->schema([
                                                Select::make('other_deduction_id')
                                                    ->label('Deduction Type')
                                                    ->options(fn() => OtherDeduction::where('status', true)->pluck('title', 'id')->toArray())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('amount')->numeric()->prefix('₱')->required(),
                                            ])->columns(2),
                                    ]),

                                Tabs\Tab::make('Mandated Deductions')
                                    ->schema([
                                        Repeater::make('govdeductionData')
                                            ->defaultItems(0)
                                            ->addAction(fn(Action $action) => $action->color('warning')->outlined())
                                            ->schema([
                                                Select::make('gov_deduction_id')
                                                    ->label('Government Agency')
                                                    ->options(fn() => GovDeduction::where('status', true)->pluck('title', 'id')->toArray())
                                                    ->searchable()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (string|null $state, Set $set) {
                                                        if (blank($state)) {
                                                            $set('amount', null);
                                                            return;
                                                        }

                                                        $deductionAmount = GovDeduction::query()
                                                            ->where('id', $state)
                                                            ->value('amount');

                                                        $set('amount', $deductionAmount ? (float) $deductionAmount : 0);
                                                    }),
                                                TextInput::make('amount')->numeric()->prefix('₱')->required(),
                                            ])->columns(2),
                                    ]),

                                Tabs\Tab::make('Adjustments')
                                    ->schema([
                                        Repeater::make('adjustmentData')
                                            ->defaultItems(0)
                                            ->addAction(fn(Action $action) => $action->color('warning')->outlined())
                                            ->schema([
                                                Select::make('adjustment_id')
                                                    ->label('Adjustment Category')
                                                    ->options(fn() => Category::where('cat', 'ADJUSTMENT')->where('status', true)->pluck('name', 'id')->toArray())
                                                    ->searchable()
                                                    ->required(),
                                                TextInput::make('amount')->numeric()->prefix('₱')->required(),
                                            ])->columns(2),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, Employee $record) {
                        $yearEndId = session('session_yearendreportspid');
                        $employeeId = $record->employeeid;

                        $recordExists = YearEndReport::where('code', $yearEndId)->exists();

                        if (! $yearEndId || ! $recordExists) {
                            Notification::make()->title('Active Year-End Report Period is invalid or missing.')->danger()->send();
                            return;
                        }

                        DB::transaction(function () use ($data, $yearEndId, $employeeId) {
                            // 1. Sync Earnings (Incentive Bonus)
                            if (isset($data['earnings']) && $data['earnings'] !== null) {
                                \App\Models\IncentiveBonus::updateOrCreate(
                                    [
                                        'yearendrepid' => $yearEndId,
                                        'employeeid'   => $employeeId,
                                    ],
                                    [
                                        'status'   => $data['earnings_status'] ?? true,
                                        'earnings' => $data['earnings'],
                                    ]
                                );
                            } else {
                                \App\Models\IncentiveBonus::where('yearendrepid', $yearEndId)
                                    ->where('employeeid', $employeeId)
                                    ->delete();
                            }

                            // 2. Sync Other Deductions
                            \App\Models\OtherDeductionLog::where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)->delete();

                            if (! empty($data['otherdeductionData'])) {
                                $otherDeductions = array_map(fn($row) => [
                                    'date_period_id'     => $yearEndId,
                                    'employee_id'        => $employeeId,
                                    'other_deduction_id' => $row['other_deduction_id'],
                                    'amount'             => $row['amount'],
                                    'created_at'         => now(),
                                    'updated_at'         => now(),
                                ], $data['otherdeductionData']);
                                \App\Models\OtherDeductionLog::insert($otherDeductions);
                            }

                            // 3. Sync Government Deductions
                            \App\Models\GovDeductionLog::where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)->delete();

                            if (! empty($data['govdeductionData'])) {
                                $govDeductions = array_map(fn($row) => [
                                    'date_period_id'   => $yearEndId,
                                    'employee_id'      => $employeeId,
                                    'gov_deduction_id' => $row['gov_deduction_id'],
                                    'amount'           => $row['amount'],
                                    'created_at'       => now(),
                                    'updated_at'       => now(),
                                ], $data['govdeductionData']);
                                \App\Models\GovDeductionLog::insert($govDeductions);
                            }

                            // 4. Sync Adjustments
                            \App\Models\Adjustment::where('date_period_id', $yearEndId)
                                ->where('employee_id', $employeeId)->delete();

                            if (! empty($data['adjustmentData'])) {
                                $adjustments = array_map(fn($row) => [
                                    'date_period_id' => $yearEndId,
                                    'employee_id'    => $employeeId,
                                    'adjustment_id'  => $row['adjustment_id'],
                                    'amount'         => $row['amount'],
                                    'created_at'     => now(),
                                    'updated_at'     => now(),
                                ], $data['adjustmentData']);
                                \App\Models\Adjustment::insert($adjustments);
                            }
                        });

                        Notification::make()->title('Earnings, deductions, and adjustments updated successfully.')->success()->send();
                    }),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('payslip')
                        ->color('success')
                        ->icon('heroicon-m-receipt-percent')
                        ->label('Payslip')
                        // Remove ->openUrlInNewTab() from here, we will trigger it via JavaScript below
                        ->action(function (Action $action, $livewire) {
                            // Generate your target bulk print URL
                            $employeeIds = $livewire->getFilteredTableQuery()->pluck('employeeid')->toArray();
                            $yearendid     = session('session_yearendreportspid');
                            $url = route('payroll.incentivebonuses-payslip', [
                                'yearendid' => $yearendid,
                                'ids'       => $employeeIds,
                            ]);
                            $action->getLivewire()->js("window.open('{$url}', '_blank')");
                        }),
                    BulkAction::make('print_reports')
                        ->label('View Reports')
                        ->icon('heroicon-m-printer')
                        ->color('success')
                        ->action(function (Action $action, $livewire) {
                            // Collect employee IDs across all currently filtered pages
                            $employeeIds = $livewire->getFilteredTableQuery()->pluck('employeeid')->toArray();
                            $yearendid     = session('session_yearendreportspid');
                            // 3. Build the route URL passing the yearendid and employee IDs
                            $url = route('incentive-bonus.breakdown', [
                                'yearendid' => $yearendid,
                                'ids'       => $employeeIds,
                            ]);

                            // 4. Open in a new tab via JS
                            $action->getLivewire()->js("window.open('{$url}', '_blank')");
                        }),
                    // DeleteBulkAction::make(),
                ]),
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
            'index' => ListIncentiveBonuses::route('/'),
            'create' => CreateIncentiveBonus::route('/create'),
            'edit' => EditIncentiveBonus::route('/{record}/edit'),
        ];
    }
}
