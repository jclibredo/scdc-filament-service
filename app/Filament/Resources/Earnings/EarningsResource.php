<?php

namespace App\Filament\Resources\Earnings;

use App\Filament\Resources\Earnings\Pages\CreateEarnings;
use App\Filament\Resources\Earnings\Pages\EditEarnings;
use App\Filament\Resources\Earnings\Pages\ListEarnings;
use App\Filament\Resources\Earnings\Schemas\EarningsForm;
use App\Filament\Resources\Earnings\Tables\EarningsTable;
use App\Models\Category;
use App\Models\Earnings;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class EarningsResource extends Resource
{
    protected static ?string $model = Earnings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Earnings';

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
                Select::make('category_id')
                    ->label('Category')
                    ->options(
                        DB::table('categories')->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('amount')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
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
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('amount')->sortable(),
                IconColumn::make('status')->boolean()->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),

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

                // 🔹 Filter by Category
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(
                        DB::table('categories')->pluck('name', 'id')
                    ),

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
