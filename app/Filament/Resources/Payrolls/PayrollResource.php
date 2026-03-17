<?php

namespace App\Filament\Resources\Payrolls;

use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\DatePeriod;
use App\Models\Payroll;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Payroll';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('employeeid')
                    ->label('Employee')
                    ->options(
                        DB::table('employees')
                            ->orderBy('lastname', 'asc')
                            ->get()
                            ->mapWithKeys(fn($emp) => [
                                $emp->employeeid => $emp->lastname . ', ' . $emp->firstname
                            ])
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('periodid')
                    ->label('Payroll Period')
                    ->options(
                        DatePeriod::all()->mapWithKeys(function ($period) {
                            $label = $period->datefrom . ' to ' . $period->dateto;
                            return [$period->id => $label];
                        })->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('totalhours')
                    ->label('Total Hours')
                    ->numeric()
                    ->required(),

                TextInput::make('acquirehours')
                    ->label('Acquire Hours')
                    ->numeric()
                    ->required(),
                // Toggle::make('status')
                //     ->label('Active')
                //     ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return PayrollsTable::configure($table);
        return $table
            ->columns([
                TextColumn::make('period.datefrom')
                    ->label('Date From')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('period.dateto')
                    ->label('Date To')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('id')->label('ID')->sortable(),
                // TextColumn::make('employeeid')->label('Employee ID')->sortable()->searchable(),
                TextColumn::make('employee.lastname') // relationship-based column
                    ->label('Employee')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->employee?->lastname . ', ' . $record->employee?->firstname
                    )
                    ->sortable() // sorts via relationship automatically
                    ->searchable(
                        query: fn($query, $search) =>
                        $query->whereHas(
                            'employee',
                            fn($q) =>
                            $q->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                        )
                    ),
                TextColumn::make('period')->label('Period')->sortable(),
                TextColumn::make('totalhours')->label('Total Hours')->sortable(),
                TextColumn::make('acquirehours')->label('Acquire Hours')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === true,
                        'danger' => fn($state) => $state === false,
                    ]),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
