<?php

namespace App\Filament\Resources\EmpSchedules;

// use App\Filament\Resources\EmpSchedules\Pages\CreateEmpSchedule;
// use App\Filament\Resources\EmpSchedules\Pages\EditEmpSchedule;
use App\Filament\Resources\EmpSchedules\Pages\ListEmpSchedules;
use App\Models\ActivityLog;
// use App\Filament\Resources\EmpSchedules\Schemas\EmpScheduleForm;
// use App\Filament\Resources\EmpSchedules\Tables\EmpSchedulesTable;
use App\Models\Employee;
use App\Models\EmpSchedule;
use App\Services\TransactionCheckService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
// use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EmpScheduleResource extends Resource
{
    protected static ?string $model = EmpSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'EmpSchedule';
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Shift Schedule Details')
                ->extraAttributes([
                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                ])
                ->columnSpanFull()
                ->description('Manage employee shift duration, time-in, and auto-calculated time-out values.')
                ->icon('heroicon-o-clock') // Optional: Sleek clock icon for time-tracking
                ->columns(3) // Optional: Dynamically splits your inputs into a clean 3-column grid layout
                ->schema([
                    Select::make('employeeid')
                        ->label('Employee')
                        ->relationship('employData')
                        ->getOptionLabelFromRecordUsing(
                            fn($record) => $record->full_name
                        )
                        ->searchable(['firstname', 'middlename', 'lastname'])
                        ->preload()
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->default(session('session_employee_id'))
                        ->columnSpanFull(),

                    TimePicker::make('timein')
                        ->label('Time-In')
                        ->seconds(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            if (blank($state)) {
                                return;
                            }
                            $workingHours = (int) ($get('workingHours') ?? 8);
                            $timeout = Carbon::parse($state)
                                ->addHours($workingHours + 1) // +1 hour break
                                ->format('H:i');
                            $set('timeout', $timeout);
                        })
                        ->required(),

                    TimePicker::make('timeout')
                        ->label('Time-Out')
                        ->seconds(false)
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    TextInput::make('workingHours')
                        ->label('Working Hours')
                        ->numeric()
                        ->default(8)
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            if (blank($get('timein'))) {
                                return;
                            }
                            $timeout = Carbon::parse($get('timein'))
                                ->addHours(((int) $state) + 1) // +1 hour break
                                ->format('H:i');
                            $set('timeout', $timeout);
                        })
                        ->required(),

                    Toggle::make('status')
                        ->default(true)
                        ->required(),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        $empid = session('session_employee_id');
        if (!$empid) {
            return $table;
        }
        $earningDetails = cache()->remember(
            "header_admission_full_{$empid}",
            3600,
            function () use ($empid) {
                return Employee::where('employeeid', $empid)
                    ->first();
            }
        );
        $emtype = $earningDetails?->empType?->name ?? 'N/A';
        $emstat = $earningDetails?->empStat?->name ?? 'N/A';
        $empname = $earningDetails?->full_name ?? 'N/A';
        $details = [
            "EMPLOYEE: {$empname}",
            "EMP. TYPE: {$emtype}",
            "EMP. STATUS: {$emstat}",
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
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->header(fn() => new HtmlString("
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
                                    Schedule for Employee ID: <span style='font-family: monospace; color: #b45309;'>{$empid}</span>
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
                if (!$user) {
                    return EmpSchedule::whereRaw('1 = 0');
                }
                // Eager load the relationships
                return EmpSchedule::query()
                    ->where('status', true)
                    ->where(
                        'employeeid',
                        session('session_employee_id')
                    );
            })
            ->columns([
                TextColumn::make('timein')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('timeout')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('workingHours')
                    ->label('Working Hours'),
                IconColumn::make('status')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('status'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Updated shift schedule for Employee ID [{$record->employeeid}]: In: {$record->timein} | Out: {$record->timeout} ({$record->workingHours} hrs) (ID: {$record->id})",
                                'module'    => 'Schedule Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        // ->visible(fn($record) => !TransactionCheckService::hasScheduleTransactions($record))
                        ->label('Update'),
                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation() // ⚠️ Adds the confirmation step before running
                        ->modalHeading('Deactivate Record')
                        ->modalDescription('This record has active transactions and cannot be deleted. Deactivating it will turn its status to inactive. Proceed?')
                        ->modalSubmitActionLabel('Yes, deactivate')
                        ->action(function ($record) {
                            // Deactivate the record
                            $record->status = false;
                            $record->save();
                            // 1. Log the deactivation activity
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deactivated shift schedule for Employee ID [{$record->employeeid}] due to active transactions (ID: {$record->id})",
                                'module'    => 'Schedule Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);

                            // 2. Display notification toast
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasScheduleTransactions($record) && ($record->status === true || $record->status == 1)),
                    DeleteAction::make()
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deleted shift schedule category for Employee ID [{$record->employeeid}] (ID: {$record->id})",
                                'module'    => 'Schedule Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->visible(fn($record) => !TransactionCheckService::hasScheduleTransactions($record))
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')   //heroicon-m-chart-bar
                    ->button()
                    ->color('success')
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
            'index' => ListEmpSchedules::route('/'),
            // 'create' => CreateEmpSchedule::route('/create'),
            // 'edit' => EditEmpSchedule::route('/{record}/edit'),
        ];
    }
}
