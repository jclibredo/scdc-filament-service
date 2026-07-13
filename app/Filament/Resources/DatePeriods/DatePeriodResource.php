<?php

namespace App\Filament\Resources\DatePeriods;

use App\Filament\Resources\DatePeriods\Pages\ListDatePeriods;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Employee;
use App\Models\GovDeduction;
use App\Models\GovDeductionLog;
use App\Models\User;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
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
    protected  static string|UnitEnum|null $navigationGroup = 'Report Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Printer;

    protected static ?string $recordTitleAttribute = 'DatePeriod';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        // If $user is an integer (ID), fetch the actual User model from the database
        if (is_int($user)) {
            $user = User::find($user);
        }
        // Check if we have a valid User model instance now
        if (! $user instanceof User) {
            return false;
        }
        return $user->userPermissions()
            ->whereIn(
                'module',
                [
                    'SUPERADMIN',
                    'IMPORT',
                    'PAYROLLADMINWEEKLY',
                    'PAYROLLADMINMONTHLY',
                    'PAYROLLSUBCONWEEKLY',
                    'PAYROLLSUBCONMONTHLY',
                    'OFFICEMANAGER',
                ]
            )
            ->exists();
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
                            ->default(function () {
                                $user = Auth::user();
                                if (!$user) return null;
                                $isSuperAdmin = $user->userPermissions()->where('module', 'SUPERADMIN')->exists();
                                if ($isSuperAdmin) return null; // Or set a specific default for Super Admin if preferred
                                $hasAdminPermission = $user->userPermissions()->whereIn('module', ['PAYROLLADMINWEEKLY', 'PAYROLLADMINMONTHLY'])->exists();
                                $hasSubConPermission = $user->userPermissions()->whereIn('module', ['PAYROLLSUBCONWEEKLY', 'PAYROLLSUBCONMONTHLY'])->exists();
                                // If they only have SUB-CON permissions, default to the SUB-CON category ID
                                if ($hasSubConPermission && !$hasAdminPermission) {
                                    return Category::where('cat', 'EMPLOYEE_TYPE')->where('name', 'SUB-CON')->value('id');
                                }
                                // If they only have ADMIN permissions, default to the ADMIN category ID
                                if ($hasAdminPermission && !$hasSubConPermission) {
                                    return Category::where('cat', 'EMPLOYEE_TYPE')->where('name', 'ADMIN')->value('id');
                                }

                                return null;
                            })
                            ->disabled(function () {
                                $user = Auth::user();
                                if (!$user) return false;
                                $isSuperAdmin = $user->userPermissions()->where('module', 'SUPERADMIN')->exists();
                                if ($isSuperAdmin) return false; // Super admin can always edit
                                $hasAdminPermission = $user->userPermissions()->whereIn('module', ['PAYROLLADMINWEEKLY', 'PAYROLLADMINMONTHLY'])->exists();
                                $hasSubConPermission = $user->userPermissions()->whereIn('module', ['PAYROLLSUBCONWEEKLY', 'PAYROLLSUBCONMONTHLY'])->exists();
                                return ($hasAdminPermission xor $hasSubConPermission);
                            })
                            ->dehydrated()
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
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->recordUrl(null)    // Disables the URL redirect on click
            ->recordAction(null)
            ->query(function () {
                $user = Auth::user();
                if (!$user) {
                    return DatePeriod::whereRaw('1 = 0');
                }
                // if (
                //     session('session_employeestatus')
                //     && session('session_employeetype')
                //     && session('session_periodcode')
                // ) {
                //     return DatePeriod::query()
                //         ->where('status', true)
                //         ->where('code', session('session_periodcode'))
                //         ->where('category_id', session('session_employeestatus'))
                //         ->where('employeetype', session('session_employeetype'));
                // }
                // return DatePeriod::query()
                //     ->where('status', true);
                // Start building the base query
                // Check if we have a valid User model instance now
                if (! $user instanceof User) {
                    return DatePeriod::whereRaw('1 = 0');
                }

                $query = DatePeriod::query()->where('status', true);

                // 1. Superadmin has bypass access to see everything
                $isSuperAdmin = $user->userPermissions()->where('module', 'SUPERADMIN')->exists();

                if (!$isSuperAdmin) {
                    // Check for specific Administrative payroll permissions
                    $hasAdminPermission = $user->userPermissions()
                        ->whereIn('module', ['PAYROLLADMINWEEKLY', 'PAYROLLADMINMONTHLY'])
                        ->exists();

                    // Check for specific Sub-Contractor payroll permissions
                    $hasSubConPermission = $user->userPermissions()
                        ->whereIn('module', ['PAYROLLSUBCONWEEKLY', 'PAYROLLSUBCONMONTHLY'])
                        ->exists();

                    // Filter database query based on the active structural permission types
                    $query->whereHas('employeeTypeCategory', function ($q) use ($hasAdminPermission, $hasSubConPermission) {
                        $q->where(function ($subQuery) use ($hasAdminPermission, $hasSubConPermission) {
                            if ($hasAdminPermission) {
                                $subQuery->orWhere('name', 'ADMIN');
                            }
                            if ($hasSubConPermission) {
                                $subQuery->orWhere('name', 'SUB-CON');
                            }

                            // If they have neither permission, make sure they see nothing
                            if (!$hasAdminPermission && !$hasSubConPermission) {
                                $subQuery->whereRaw('1 = 0');
                            }
                        });
                    });
                }

                // 2. Existing Session-Based Filters
                if (
                    session('session_employeestatus')
                    && session('session_employeetype')
                    && session('session_periodcode')
                ) {
                    return $query->where('code', session('session_periodcode'))
                        ->where('category_id', session('session_employeestatus'))
                        ->where('employeetype', session('session_employeetype'));
                }

                return $query;
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
                        ->color('warning')
                        // ->button()
                        // ->outlined()
                        ->icon('heroicon-m-arrow-right-circle')
                        ->action(function (DatePeriod $record) {
                            session(['session_periodcode' => $record->code]);
                            session(['session_partners' => $record->partners]);
                            session(['session_employeetype' => $record->employeetype]);
                            session(['session_employeestatus' => $record->category_id]);
                            // dd(session('session_periodcode'), session('session_partners'), session('session_employeetype'), session('session_employeestatus'));
                            return redirect(PayrollResource::getUrl('index'));
                        }),
                    Action::make('gov_contribution')
                        ->label('Deductables')
                        ->icon('heroicon-m-minus-circle')
                        ->color('danger')
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
                        ->visible(fn($record) => !TransactionCheckService::hasDatePeriodTransactions($record))
                        ->label('Update'),
                    DeleteAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasDatePeriodTransactions($record))
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
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
            'index' => ListDatePeriods::route('/'),
            // 'create' => CreateDatePeriod::route('/create'),
            // 'edit' => EditDatePeriod::route('/{record}/edit'),
        ];
    }
}
