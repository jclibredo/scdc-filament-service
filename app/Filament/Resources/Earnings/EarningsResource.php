<?php

namespace App\Filament\Resources\Earnings;

use App\Filament\Resources\Earnings\Pages\CreateEarnings;
use App\Filament\Resources\Earnings\Pages\EditEarnings;
use App\Filament\Resources\Earnings\Pages\ListEarnings;
use App\Models\Category;
use App\Models\Earnings;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                    ->schema([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->relationship('employee')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name)
                            ->searchable(['firstname', 'middlename', 'lastname'])
                            ->preload()
                            ->required()
                            ->disabled(true)
                            ->dehydrated()
                            // Set the default value from the session
                            ->default(session('earnings_employeeid')),
                        Select::make('title')
                            ->label('Earnings Type')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Earnings::whereRaw('1 = 0');
                }
                // Eager load the relationships
                return Earnings::query()
                    ->where('employee_id', session('earnings_employeeid'));
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
            ->filters([
                // 🔹 Filter by Employee
                SelectFilter::make('employeeid')
                    ->label('Employee')
                    ->options(
                        DB::table('employees')
                            ->orderBy('lastname')
                            ->get()
                            ->mapWithKeys(fn($emp) => [
                                $emp->employeeid => $emp->lastname . ', ' . $emp->firstname
                            ])
                    ),

                // 🔹 Filter by Earnings Type
                SelectFilter::make('title')
                    ->label('Earnings Type')
                    ->options([
                        'basicpay' => 'Basic Pay',
                        'foodallowance' => 'Food Allowance',
                        'transportallowance' => 'Transpo Allowance',
                        'clothingallowance' => 'Clothing Allowance',
                    ]),

                // 🔹 Filter by Status (boolean)
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
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
            'index' => ListEarnings::route('/'),
            'create' => CreateEarnings::route('/create'),
            'edit' => EditEarnings::route('/{record}/edit'),
        ];
    }
}
