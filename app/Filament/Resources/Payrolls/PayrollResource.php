<?php

namespace App\Filament\Resources\Payrolls;

use App\Filament\Resources\Atlogs\AtlogResource;
use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Adjustment;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
// use App\Models\GovDeduction;
// use App\Models\GovDeductionLog;
use App\Models\OtherDeduction;
use App\Models\OtherDeductionLog;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\Width;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PayrollResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Payroll';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $periodcode     = session('session_periodcode');
        $sessionStatus  = session('session_employeestatus');
        $sessionType    = session('session_employeetype');
        if (!$periodcode || !$sessionStatus || !$sessionType) {
            return $table;
        }
        $datePerioDetails = cache()->remember(
            "header_admission_full_{$periodcode}",
            3600,
            function () use ($periodcode) {
                return DatePeriod::where('code', $periodcode)
                    ->where('status', true)
                    ->first();
            }
        );
        $emtype = $datePerioDetails?->employeeTypeCategory?->name ?? 'N/A';
        $emstat = $datePerioDetails?->category?->name ?? 'N/A';
        $startdate = $datePerioDetails->datefrom
            ? Carbon::parse($datePerioDetails->datefrom)->format('M d, Y') : 'N/A';
        $enddate = $datePerioDetails->dateto
            ? Carbon::parse($datePerioDetails->dateto)->format('M d, Y') : 'N/A';

        $partnerSession = session('session_partners');
        $details = [
            "DATE START: {$startdate}",
            "DATE END: {$enddate}",
            "EMP TYPE: {$emtype}",
            "EMP STATUS: {$emstat}",
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
                                    DATE PERIOD CODE : <span style='font-family: monospace; color: #b45309;'>{$periodcode}</span>
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
                $datePeriodData = DatePeriod::where('code', session('session_periodcode'))
                    ->where('status', true)
                    ->first();
                if (! $datePeriodData) {
                    return Employee::query()->whereRaw('1 = 0');
                }
                $query = Employee::query()
                    ->where('status', true)
                    ->where('datehired', '<=', $datePeriodData->datefrom);
                if (! session('session_employeestatus') || ! session('session_employeetype')) {
                    return $query;
                }
                $query->where('empstatus', session('session_employeestatus'))
                    ->where('employeetype', session('session_employeetype'));
                $partnerSession = session('session_partners');
                if ($partnerSession && $partnerSession !== 'ALL') {
                    $query->where('partners', $partnerSession);
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
            ->filters([])
            ->bulkActions([
                BulkActionGroup::make([

                    BulkAction::make('payslip')
                        ->color('success')
                        ->icon('heroicon-m-receipt-percent')
                        ->label('Payslip')
                        // Remove ->openUrlInNewTab() from here, we will trigger it via JavaScript below
                        ->action(function (Action $action, Collection $records) {
                            // Generate your target bulk print URL
                            $periodcode = session('session_periodcode');
                            $ids = $records->pluck('id')->toArray();
                            $partnerss = session('session_partners') ?? 0;
                            $empids = $records->pluck('employeeid')->map(fn($id) => (int) $id)->toArray();
                            $existingInPayrollSummary = [];
                            if (!empty($empids)) {
                                $existingInPayrollSummary = DB::table('payroll_summary_reports')
                                    ->where('status', true)
                                    ->whereIn('employee_id', $empids)
                                    ->pluck('employee_id')
                                    ->toArray();
                            }

                            // 1. Calculate missing IDs
                            $missingInPayroll = array_diff($empids, $existingInPayrollSummary);

                            // 2. Guard: If any IDs are missing, block processing and show the HTML alert
                            if (!empty($missingInPayroll)) {
                                $missingList = implode(', ', $missingInPayroll);
                                Notification::make()
                                    ->danger()
                                    ->title('Action Blocked')
                                    ->body(new HtmlString("
                                            <div x-data='{ show: true }' x-show='show' class='p-3 border border-danger-500 rounded-lg bg-danger-50 dark:bg-red-950/20 flex items-start gap-3 relative'>
                                                <div class='text-danger-600 dark:text-danger-400 mt-0.5 flex-shrink-0'>
                                                    <svg class='w-5 h-5' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                                                    </svg>
                                                </div>
                                                
                                                <div class='text-left pr-6'>
                                                    <h4 class='font-bold text-danger-800 dark:text-danger-400 text-sm leading-tight'>Missing Payroll Summaries</h4>
                                                    <p class='text-xs text-danger-700 dark:text-danger-300 mt-1 leading-relaxed'>
                                                        Cannot generate payslips. The following Employee IDs do not have records in the payroll summary: 
                                                        <strong class='font-mono bg-danger-100 dark:bg-danger-900/40 px-1 rounded'>{$missingList}</strong>
                                                    </p>
                                                </div>
                                                
                                                <button @click='show = false' type='button' class='absolute top-2 right-2 text-danger-500 hover:text-danger-700 dark:hover:text-danger-300 transition-colors'>
                                                    <svg class='w-4 h-4' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' d='M6 18L18 6M6 6l12 12'></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        "))
                                    ->persistent() // Ensures the notification stays on screen until dismissed
                                    ->send();

                                return; // Hault executing window.open() below
                            }

                            $url = route('payroll.bulk-payslip', [
                                'ids' => $ids,
                                'periodcode' => $periodcode,
                                'expartners' => $partnerss,
                            ]);
                            $action->getLivewire()->js("window.open('{$url}', '_blank')");
                        }),
                    BulkAction::make('payment')
                        ->label('Process Cut-off')
                        ->icon('heroicon-m-cog')
                        ->color('warning')
                        ->form(function (Collection $records) {
                            $periodcode = session('session_periodcode');
                            // $partners = session('session_partners');
                            $employeeIds = $records->pluck('employeeid')->toArray();
                            $existingInEarnings = [];
                            $existingSchedule = [];
                            if (!empty($employeeIds)) {
                                $existingInEarnings = DB::table('earnings')
                                    ->where('status', true)
                                    ->where('hierarchy', 'PRIMARY')
                                    ->whereIn('employee_id', $employeeIds) // Ensure this matches your earnings table column name
                                    ->pluck('employee_id')
                                    ->toArray();
                                $existingSchedule = DB::table('emp_schedule')
                                    ->where('status', true)
                                    ->whereIn('employeeid', $employeeIds) // Ensure this matches your earnings table column name
                                    ->pluck('employeeid')
                                    ->toArray();
                            }
                            // 2. Find if any of the selected employees are missing from that list
                            $missingInEarnings = array_diff($employeeIds, $existingInEarnings);
                            $missingSchedule = array_diff($employeeIds, $existingSchedule);

                            if (
                                empty($employeeIds)
                                || !$periodcode
                                || !empty($missingInEarnings)
                                || !empty($missingSchedule)
                            ) {
                                $missingBoth = array_intersect($missingInEarnings, $missingSchedule);
                                $errorMessage = match (true) {
                                    empty($employeeIds) && !$periodcode => 'Both active session configurations and selected employees are missing.',
                                    empty($employeeIds) => 'No employees were selected. Please select at least one employee to process.',
                                    !$periodcode => 'Active session period code configuration is missing.',

                                    // 🌟 1. Check if BOTH are missing first
                                    !empty($missingInEarnings) && !empty($missingSchedule)
                                    => 'Processing failed. Some employees are missing both basic earnings and schedule records. | SCHEDULES =>
                                    ' . implode(', ', $missingSchedule) . ' | EARNINGS =>' . implode(', ', $missingInEarnings),

                                    // 2. Check if ONLY earnings records are missing
                                    !empty($missingInEarnings)
                                    => 'Processing failed. Some employee has no basic earnings record found for Employee IDs: ' . implode(', ', $missingInEarnings),

                                    // 3. Check if ONLY schedule records are missing
                                    !empty($missingSchedule)
                                    => 'Processing failed. Some employee has no schedule record found for Employee IDs: ' . implode(', ', $missingSchedule),

                                    default => 'An unexpected processing error occurred.'
                                };

                                return [
                                    Placeholder::make('warning_message')
                                        ->label(false)
                                        ->content(new HtmlString("
                                            <div class='p-3 border border-danger-500 rounded-lg bg-danger-50 dark:bg-red-950/20 flex items-center gap-3'>
                                                <div style='color: #dc2626; flex-shrink: 0; display: flex; align-items: center;'>
                                                    <svg style='width: 1.25rem; height: 1.25rem;' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                                                    </svg>
                                                </div>
                                                <div class='text-left'>
                                                    <h4 style='font-weight: 700; color: #991b1b; font-size: 0.85rem; tracking-tight: 0.025em; line-height: 1.25;'>Processing Failed</h4>
                                                    <p style='font-size: 0.75rem; color: #b91c1c; line-height: 1.5; margin-top: 0.15rem;'>" . e($errorMessage) . "</p>
                                                </div>
                                            </div>
                                        ")),
                                ];
                            }
                            return [
                                Placeholder::make('confirmation_message')
                                    ->label(false)
                                    ->content('Are you sure you want to process the cut-off sheets for the selected employees?'),
                            ];
                        })
                        ->modalWidth(
                            fn(Collection $records) => (empty($records->pluck('employeeid')->toArray()) || !session('session_periodcode'))
                                ? Width::Small
                                : Width::Medium
                        )
                        // 3. REMOVE SUBMIT BUTTON IF TRUE: Returns null to completely strip it from the footer markup
                        ->modalSubmitAction(function (Action $action, Collection $records) {
                            $periodcode = session('session_periodcode');
                            $employeeIds = $records->pluck('employeeid')->toArray();
                            $existingInEarnings = DB::table('earnings')
                                ->where('status', true)
                                ->where('hierarchy', 'PRIMARY')
                                ->whereIn('employee_id', $employeeIds) // Ensure this matches your earnings table column name
                                ->pluck('employee_id')
                                ->toArray();
                            $schedules = DB::table('emp_schedule')
                                ->where('status', true)
                                ->whereIn('employeeid', $employeeIds) // Ensure this matches your earnings table column name
                                ->pluck('employeeid')
                                ->toArray();
                            $missingSchedule = array_diff($employeeIds, $schedules);
                            $missingInEarnings = array_diff($employeeIds, $existingInEarnings);
                            if (
                                empty($employeeIds) || !$periodcode
                                || !empty($missingInEarnings)
                                || !empty($missingSchedule)
                            ) {
                                return $action->hidden();
                            }
                            return $action
                                ->label('Proceed')
                                ->icon('heroicon-m-arrow-right-end-on-rectangle');
                        })
                        ->modalCancelActionLabel(
                            fn(Collection $records) => (empty($records->pluck('employeeid')->toArray()) || !session('session_periodcode')) ? 'Close' : 'Cancel'
                        )
                        // 4. Run the redirect execution payload if valid
                        ->action(function (BulkAction $action, Collection $records) {
                            $periodcode = session('session_periodcode');
                            $employeeIds = $records->pluck('employeeid')->toArray();
                            $partnerss = session('session_partners') ?? 0;
                            $url = route('payroll.process-sheet', [
                                'periodcode' => $periodcode,
                                'employees' => $employeeIds,
                                'expartners' => $partnerss,
                            ]);

                            $action->getLivewire()->js("window.open('{$url}', '_blank')");
                        }),
                ])
                    ->button()
                    ->outlined()
                    ->icon('heroicon-m-chart-bar')
                    ->color('success')
                    ->label(' Actions'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_timesheet')
                        ->color('warning')
                        ->icon('heroicon-m-calendar-days')
                        ->label('Timesheet')
                        ->action(function (Employee $record) {
                            session([
                                'session_employee_id' => $record->employeeid,
                                'session_periodcode' => session('session_periodcode'),
                                'session_employeetype' => $record->employeetype,
                                'session_employeestatus' => $record->empstatus,
                            ]);
                            return redirect(AtlogResource::getUrl('index'));
                        }),
                    //OTHER. DEDUCTIONS
                    Action::make('other_contribution')
                        ->label('Other Deduction')
                        ->icon('heroicon-m-minus-circle')
                        ->color('danger')
                        ->modalHeading('Other Deductions')
                        ->modalWidth('lg') // Widened to 'lg' so the selection option and amount sit cleanly side-by-side
                        ->form(function ($record) {
                            return [
                                Repeater::make('deductions')
                                    ->label('Deduction Entries')
                                    ->schema([
                                        Select::make('other_deduction_id')
                                            ->label('Deduction Type')
                                            ->options(
                                                OtherDeduction::query()
                                                    ->pluck('title', 'id')
                                                    ->toArray()
                                            )
                                            ->placeholder('Select deduction...')
                                            ->required()
                                            ->searchable()
                                            ->native(false)
                                            ->distinct() // Prevents choosing the same type more than once in the same repeater block
                                            ->columnSpan(2),

                                        TextInput::make('amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->placeholder('0.00')
                                            ->minValue(0)
                                            ->required()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3) // Organizes inputs neatly across columns
                                    ->statePath('deductions')
                                    // 1. Fetch, combine, and pre-populate saved deduction items + amounts when modal launches
                                    ->formatStateUsing(function () use ($record) {
                                        return OtherDeductionLog::where('date_period_id', session('session_periodcode'))
                                            ->where('employee_id', $record->employeeid)
                                            ->get(['other_deduction_id', 'amount'])
                                            ->toArray() ?? [];
                                    })
                                    ->createItemButtonLabel('Add New Deduction Row'),
                            ];
                        })
                        ->action(function (array $data, $record) {
                            $repeaterItems = data_get($data, 'deductions', []);
                            $periodCode = session('session_periodcode');
                            DB::transaction(function () use ($repeaterItems, $record, $periodCode) {
                                OtherDeductionLog::where('date_period_id', $periodCode)
                                    ->where('employee_id', $record->employeeid)
                                    ->delete();

                                if (empty($repeaterItems)) {
                                    return;
                                }
                                $timestamp = now();
                                $insertData = [];
                                // 3. Construct payload data with the accurate user-supplied amount variants
                                foreach ($repeaterItems as $item) {
                                    if (empty($item['other_deduction_id'])) {
                                        continue;
                                    }
                                    $insertData[] = [
                                        'other_deduction_id' => $item['other_deduction_id'],
                                        'employee_id'        => $record->employeeid,
                                        'date_period_id'     => $periodCode,
                                        'amount'             => data_get($item, 'amount', 0.00),
                                        'created_at'         => $timestamp,
                                        'updated_at'         => $timestamp,
                                    ];
                                }
                                foreach (array_chunk($insertData, 250) as $chunk) {
                                    OtherDeductionLog::insert($chunk);
                                }
                            });
                            Notification::make()
                                ->title('Other Deductions Updated Successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('payroll_adjustment')
                        ->label('Adjustment')
                        ->icon('heroicon-m-plus-circle')
                        ->color('success')
                        ->modalHeading('Salary Adjustment')
                        ->modalWidth('lg')
                        ->form(function ($record) {
                            return [
                                Repeater::make('adjustments')
                                    ->label('Adjustment Entries')
                                    ->schema([
                                        Select::make('adjustment_id')
                                            ->label('Adjustment Type')
                                            ->options(
                                                Category::query()
                                                    ->where('cat', 'ADJUSTMENT')
                                                    ->where('status', true) // Only list active categories
                                                    ->pluck('name', 'id')
                                                    ->toArray()
                                            )
                                            ->placeholder('Select adjust...')
                                            ->required()
                                            ->searchable()
                                            ->native(false)
                                            ->distinct()
                                            ->columnSpan(2),
                                        TextInput::make('amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->inputMode('decimal')
                                            ->placeholder('0.00')
                                            ->minValue(0)
                                            ->required()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3)
                                    ->statePath('adjustments')
                                    ->formatStateUsing(function () use ($record) {
                                        return Adjustment::where('date_period_id', session('session_periodcode'))
                                            ->where('employee_id', $record->employeeid)
                                            ->get(['adjustment_id', 'amount'])
                                            ->toArray() ?? [];
                                    })
                                    ->createItemButtonLabel('Add New Adjustment'),
                            ];
                        })
                        ->action(function (array $data, $record) {
                            $repeaterItems = data_get($data, 'adjustments', []);
                            $periodCode = session('session_periodcode');
                            DB::transaction(function () use ($repeaterItems, $record, $periodCode) {
                                // Clean Wipe Step
                                Adjustment::where('date_period_id', $periodCode)
                                    ->where('employee_id', $record->employeeid)
                                    ->delete();
                                if (empty($repeaterItems)) {
                                    return;
                                }
                                $timestamp = now();
                                $insertData = [];
                                foreach ($repeaterItems as $item) {
                                    if (empty($item['adjustment_id'])) {
                                        continue;
                                    }
                                    $insertData[] = [
                                        'adjustment_id'  => $item['adjustment_id'],
                                        'employee_id'    => $record->employeeid,
                                        'date_period_id' => $periodCode,
                                        'amount'         => data_get($item, 'amount', 0.00),
                                        'created_at'     => $timestamp,
                                        'updated_at'     => $timestamp,
                                    ];
                                }
                                foreach (array_chunk($insertData, 250) as $chunk) {
                                    Adjustment::insert($chunk);
                                }
                            });
                            Notification::make()
                                ->title('Adjustment Updated Successfully')
                                ->success()
                                ->send();
                        }),
                    // Action::make('payroll_summary')
                    //     ->label('View Summary')
                    //     ->icon('heroicon-m-printer') // Fixed printer icon
                    //     ->color('success')
                    //     ->url(fn($record) => route('payroll.summary', [
                    //         'employee_id' => $record->employeeid,
                    //         'period_code' => session('session_periodcode')
                    //     ]))
                    //     ->openUrlInNewTab(),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')   //heroicon-m-chart-bar
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
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
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
