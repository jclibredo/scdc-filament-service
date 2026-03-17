<?php

namespace App\Filament\Resources\HolidayLogs;

use App\Filament\Resources\HolidayLogs\Pages\CreateHolidayLogs;
use App\Filament\Resources\HolidayLogs\Pages\EditHolidayLogs;
use App\Filament\Resources\HolidayLogs\Pages\ListHolidayLogs;
use App\Models\HolidayLogs;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class HolidayLogsResource extends Resource
{
    protected static ?string $model = HolidayLogs::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'HolidayLogs';

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
                Select::make('holidayid')
                    ->label('Holiday')
                    ->options(
                        DB::table('holidays')->pluck('type', 'id')
                    )
                    ->searchable()
                    ->required(),

                TextInput::make('numberofhours')
                    ->label('Number of Hours')
                    ->numeric()
                    ->required(),

                DatePicker::make('date')
                    ->label('Holiday Date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('holiday.type')->label('Holiday')->sortable(),
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
                TextColumn::make('holiday.type')->label('Holiday')->sortable(),
                TextColumn::make('numberofhours')->label('Hours')->sortable(),
                TextColumn::make('date')->label('Holiday Date')->date()->sortable(),
            ])
            ->filters([
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
                SelectFilter::make('holidayid')
                    ->label('Holiday')
                    ->options(
                        DB::table('holidays')->pluck('type', 'id')
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListHolidayLogs::route('/'),
            'create' => CreateHolidayLogs::route('/create'),
            'edit' => EditHolidayLogs::route('/{record}/edit'),
        ];
    }
}
