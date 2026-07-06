<?php

namespace App\Filament\Resources\ThirteenthMonthLogs\Pages;

use App\Filament\Resources\ThirteenthMonthLogs\ThirteenthMonthLogsResource;
use App\Filament\Resources\ThirteenthMonths\ThirteenthMonthResource;
use App\Models\Category;
use App\Models\Employee;
use App\Models\GovDeduction;
use App\Models\OtherDeduction;
use App\Models\ThirteenthMonth;
use App\Models\YearEndReport;
// use App\Models\ThirteenthMonth;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
// use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

// use Filament\Schemas\Components\Tabs\Tab;

class ListThirteenthMonthLogs extends ListRecords
{
    protected static string $resource = ThirteenthMonthLogsResource::class;

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
                    $yearendid     = session('session_yearendreportspid');
                    $partners     = session('session_partnersid');
                    $emptype     = session('session_employeetypeid');
                    $empstatus     = session('session_employeestatusid');
                    $projectid     = session('session_projectid');
                    $rep_type     = session('session_reptype');

                    session()->forget([
                        'session_yearendreportspid',
                        'session_partnersid',
                        'session_employeetypeid',
                        'session_employeestatusid',
                        'session_projectid',
                        'session_reptype',
                        'session_empployeeid',
                    ]);
                    session([
                        'session_yearendreportspid' => $yearendid,
                        'session_partnersid'        => $partners,
                        'session_employeetypeid'    => $emptype,
                        'session_employeestatusid'  => $empstatus,
                        'session_projectid'         => $projectid,
                        'session_reptype'           => $rep_type,
                    ]);
                    return redirect()->to(ThirteenthMonthResource::getUrl('index'));
                }),

            CreateAction::make()
                ->label('Add Data')
                ->color('warning')
                ->icon('heroicon-m-plus-circle'),


            Action::make('manage_deductions')
                ->label('Deductions/Adjustments')
                ->icon('heroicon-m-adjustments-vertical')
                ->color('info')
                ->button()
                ->modalHeading("Manage Deductions & Adjustments")
                // 🟢 FIXED: Using Form $form object and explicitly calling ->fill()
                ->mountUsing(function (Schema $form) {
                    $sessionEmpId = session('session_empployeeid');
                    $yearEndId = session('session_yearendreportspid');

                    $formData = [
                        'otherdeductionData' => [],
                        'govdeductionData' => [],
                        'adjustmentData' => [],
                    ];

                    if ($sessionEmpId && $yearEndId) {
                        // Fetch Other Deductions
                        $formData['otherdeductionData'] = \App\Models\OtherDeductionLog::query()
                            ->where('date_period_id', $yearEndId)
                            ->where('employee_id', $sessionEmpId)
                            ->get(['other_deduction_id', 'amount'])
                            ->map(fn($item) => [
                                'other_deduction_id' => $item->other_deduction_id,
                                'amount' => (float) $item->amount,
                            ])
                            ->toArray();

                        // Fetch Mandated/Gov Deductions
                        $formData['govdeductionData'] = \App\Models\GovDeductionLog::query()
                            ->where('date_period_id', $yearEndId)
                            ->where('employee_id', $sessionEmpId)
                            ->get(['gov_deduction_id', 'amount'])
                            ->map(fn($item) => [
                                'gov_deduction_id' => $item->gov_deduction_id,
                                'amount' => (float) $item->amount,
                            ])
                            ->toArray();

                        // Fetch Adjustments
                        $formData['adjustmentData'] = \App\Models\Adjustment::query()
                            ->where('date_period_id', $yearEndId)
                            ->where('employee_id', $sessionEmpId)
                            ->get(['adjustment_id', 'amount'])
                            ->map(fn($item) => [
                                'adjustment_id' => $item->adjustment_id,
                                'amount' => (float) $item->amount,
                            ])
                            ->toArray();
                    }

                    // Force Filament to explicitly hydrate the form fields with the loaded arrays
                    $form->fill($formData);
                })
                ->form([
                    Tabs::make('Deductions and Adjustments')
                        ->extraAttributes([
                            'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                        ])
                        ->tabs([
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
                                                // 🟢 1. Make the field live so it reacts immediately upon selection
                                                ->live()
                                                // 🟢 2. Use the hook to find the deduction amount and set it
                                                ->afterStateUpdated(function (string|null $state, Set $set) {
                                                    if (blank($state)) {
                                                        $set('amount', null);
                                                        return;
                                                    }
                                                    // Query the target item's set amount
                                                    $deductionAmount = GovDeduction::query()
                                                        ->where('id', $state)
                                                        ->value('amount'); // Replace 'amount' with your actual column name in GovDeduction table

                                                    // Automatically update the sibling 'amount' field inside this repeater row
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
                ->action(function (array $data) {
                    $sessionEmpId = session('session_empployeeid');
                    $yearEndId = session('session_yearendreportspid');

                    $recordExists = YearEndReport::where('code', $yearEndId)->exists();
                    $employeeExists = Employee::where('employeeid', $sessionEmpId)->exists();

                    if (!$employeeExists || !$recordExists) {
                        Notification::make()->title('Required records are missing.')->danger()->send();
                        return;
                    }

                    DB::transaction(function () use ($data, $yearEndId, $sessionEmpId) {
                        // 1. Sync Other Deductions
                        \App\Models\OtherDeductionLog::where('date_period_id', $yearEndId)
                            ->where('employee_id', $sessionEmpId)->delete();
                        if (!empty($data['otherdeductionData'])) {
                            $otherDeductions = array_map(fn($row) => [
                                'date_period_id'     => $yearEndId,
                                'employee_id'        => $sessionEmpId,
                                'other_deduction_id' => $row['other_deduction_id'],
                                'amount'             => $row['amount'],
                                'created_at'         => now(),
                                'updated_at'         => now(),
                            ], $data['otherdeductionData']);
                            \App\Models\OtherDeductionLog::insert($otherDeductions);
                        }

                        // 2. Sync Government Deductions
                        \App\Models\GovDeductionLog::where('date_period_id', $yearEndId)->where('employee_id', $sessionEmpId)->delete();
                        if (!empty($data['govdeductionData'])) {
                            $govDeductions = array_map(fn($row) => [
                                'date_period_id'   => $yearEndId,
                                'employee_id'      => $sessionEmpId,
                                'gov_deduction_id' => $row['gov_deduction_id'],
                                'amount'           => $row['amount'],
                                'created_at'       => now(),
                                'updated_at'       => now(),
                            ], $data['govdeductionData']);
                            \App\Models\GovDeductionLog::insert($govDeductions);
                        }

                        // 3. Sync Adjustments
                        \App\Models\Adjustment::where('date_period_id', $yearEndId)->where('employee_id', $sessionEmpId)->delete();
                        if (!empty($data['adjustmentData'])) {
                            $adjustments = array_map(fn($row) => [
                                'date_period_id' => $yearEndId,
                                'employee_id'    => $sessionEmpId,
                                'adjustment_id'  => $row['adjustment_id'],
                                'amount'         => $row['amount'],
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ], $data['adjustmentData']);
                            \App\Models\Adjustment::insert($adjustments);
                        }
                    });

                    Notification::make()->title('Records updated successfully')->success()->send();
                }),






        ];
    }
}
