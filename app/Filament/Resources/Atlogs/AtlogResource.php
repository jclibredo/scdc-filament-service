<?php

namespace App\Filament\Resources\Atlogs;

use App\Filament\Resources\Atlogs\Pages\CreateAtlog;
use App\Filament\Resources\Atlogs\Pages\EditAtlog;
use App\Filament\Resources\Atlogs\Pages\ListAtlogs;
use App\Filament\Resources\Atlogs\Schemas\AtlogForm;
use App\Filament\Resources\Atlogs\Tables\AtlogsTable;
use App\Models\Atlog;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AtlogResource extends Resource
{
    protected static ?string $model = Atlog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Atlog';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('employeeid')
                    ->label('Employee')
                    ->options(
                        Employee::orderBy('lastname', 'asc')
                            ->get()
                            ->mapWithKeys(fn($emp) => [
                                $emp->employeeid => $emp->fullname
                            ])
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                // Select::make('employeeid')
                //     ->label('Employee')
                //     ->searchable()
                //     ->preload()
                //     ->getSearchResultsUsing(function (string $search) {
                //         return Employee::query()
                //             ->where('firstname', 'like', "%{$search}%")
                //             ->orWhere('lastname', 'like', "%{$search}%")
                //             ->limit(50)
                //             ->get()
                //             ->mapWithKeys(fn($employee) => [
                //                 $employee->employeeid => $employee->fullname
                //             ]);
                //     })
                //     ->getOptionLabelUsing(fn($value) => Employee::find($value)?->fullname)
                //     ->required(),

                // Select::make('employeeid')
                //     ->label('Employee')
                //     ->options(
                //         Employee::pluck('fullname', 'employeeid') // will work if fullname accessor exists
                //     )
                //     ->searchable()
                //     ->required(),

                DatePicker::make('date')
                    ->required(),

                TimePicker::make('time_in')
                    ->label('Time In')
                    ->seconds(false)
                    ->required(),

                TimePicker::make('break_out')
                    ->label('Break Out')
                    ->seconds(false),

                TimePicker::make('break_in')
                    ->label('Break In')
                    ->seconds(false),

                TimePicker::make('time_out')
                    ->label('Time Out')
                    ->seconds(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            // ->query(function () {
            //     $user = Auth::user();
            //     if (! $user || ! $user->facility) {
            //         return Cbc::whereRaw('1 = 0');
            //     }
            //     // Eager load the relationships
            //     return Cbc::query()
            //         ->where('main_accreno', $user->facility);
            // })
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

                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('time_in')->label('Time In')->time(),
                TextColumn::make('break_out')->label('Break Out')->time(),
                TextColumn::make('break_in')->label('Break In')->time(),
                TextColumn::make('time_out')->label('Time Out')->time(),
            ])
            ->filters([
                // SelectFilter::make('year')
                //     ->label('Year')
                //     ->options(
                //         Atlog::query()
                //             ->selectRaw('YEAR(date) as year')
                //             ->distinct()
                //             ->pluck('year', 'year')
                //             ->toArray()
                //     )
                //     ->query(fn($query, $value) => $query->whereYear('date', $value)),
                // SelectFilter::make('month')
                //     ->label('Month')
                //     ->options([
                //         '01' => 'January',
                //         '02' => 'February',
                //         '03' => 'March',
                //         '04' => 'April',
                //         '05' => 'May',
                //         '06' => 'June',
                //         '07' => 'July',
                //         '08' => 'August',
                //         '09' => 'September',
                //         '10' => 'October',
                //         '11' => 'November',
                //         '12' => 'December',
                //     ])
                //     ->query(fn($query, $value) => $query->whereMonth('date', $value)),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => ListAtlogs::route('/'),
            'edit' => EditAtlog::route('/{record}/edit'),
        ];
    }
}
