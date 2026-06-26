<?php

namespace App\Filament\Resources\Earnings;

use App\Filament\Resources\Earnings\Pages\ListEarnings;
use App\Models\Category;
use App\Models\Earnings;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
// use Illuminate\View\View;

class EarningsResource extends Resource
{
    protected static ?string $model = Earnings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Earnings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Earnings Information')
                    ->description('Configure the employee earning details.')
                    ->icon('heroicon-o-banknotes')
                    ->columnSpanFull()
                    ->schema([

                        Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee')
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

                        Select::make('title')
                            ->label('Earnings Type')
                            ->options(
                                Category::where('cat', 'EARNINGS')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        Select::make('frequency')
                            ->label('Frequency')
                            ->options([
                                'DAILY' => 'Daily',
                                'CUT-OFF' => 'Cut-Off',
                            ])
                            ->placeholder('Select frequency')
                            ->native(false)
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₱')
                            ->placeholder('0.00')
                            ->required(),
                        Select::make('hierarchy')
                            ->label('Hierarchy')
                            ->required()
                            ->options([
                                'PRIMARY' => 'PRIMARY',
                                'SECONDARY' => 'SECONDARY',
                            ])
                            ->placeholder('Select hierarchy level')
                            ->native(false)
                            ->columnSpanFull()
                            // --- Add validation rules below ---
                            ->rules([
                                fn($get, $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    if ($value === 'PRIMARY') {
                                        $employeeId = $get('employee_id');
                                        // Check if a PRIMARY record already exists for this employee
                                        $exists = Earnings::where('employee_id', $employeeId)
                                            ->where('hierarchy', 'PRIMARY')
                                            // If editing an existing record, ignore itself
                                            ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                                            ->exists();
                                        if ($exists) {
                                            $fail('This employee already has a PRIMARY earnings record.');
                                        }
                                    }
                                },
                            ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $empid = session('session_employee_id');
        if (! $empid) {
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
                                    Earnings for Employee ID: <span style='font-family: monospace; color: #b45309;'>{$empid}</span>
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
                    return Earnings::whereRaw('1 = 0');
                }
                // Eager load the relationships
                return Earnings::query()
                    ->where(
                        'employee_id',
                        session('session_employee_id')
                    );
            })
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    // Using the accessor logic: Lastname, Firstname Middlename
                    ->formatStateUsing(function ($record) {
                        $employee = $record->employee;
                        if (!$employee) return '-';

                        return "{$employee->lastname}, {$employee->firstname} {$employee->middlename}";
                    })
                    ->sortable(['lastname', 'firstname']) // Tells Filament which columns to use for sorting
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('employee', function ($q) use ($search) {
                            $q->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%")
                                ->orWhere('employeeid', 'like', "%{$search}%");
                        });
                    }),
                // TextColumn::make('title')->label('Earnings Type')->sortable(),
                TextColumn::make('category.name')->label('Earnings Type')->sortable(),
                TextColumn::make('amount')->sortable(),
                TextColumn::make('frequency')->sortable(),
                TextColumn::make('hierarchy')
                    ->label('Hierarchy')
                    ->sortable()
                    ->searchable()
                    ->badge() // Formats the text inside a nice background badge
                    ->color(fn(string $state): string => match ($state) {
                        'PRIMARY' => 'success',   // Green badge
                        'SECONDARY' => 'warning', // Amber/Yellow badge
                        default => 'gray',
                    })
                    ->default('-'),
                IconColumn::make('status')->boolean()->label('Active'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
            ])
            ->filters([]);
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
            'index' => ListEarnings::route('/'),
        ];
    }
}
