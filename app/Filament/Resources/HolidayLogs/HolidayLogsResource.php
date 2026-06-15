<?php

namespace App\Filament\Resources\HolidayLogs;

use App\Filament\Resources\HolidayLogs\Pages\CreateHolidayLogs;
use App\Filament\Resources\HolidayLogs\Pages\EditHolidayLogs;
use App\Filament\Resources\HolidayLogs\Pages\ListHolidayLogs;
use App\Models\DatePeriod;
use App\Models\HolidayLogs;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class HolidayLogsResource extends Resource
{
    protected static ?string $model = HolidayLogs::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $recordTitleAttribute = 'HolidayLogs';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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

                // 💡 NEW: Date Period lookup from your session or date periods database table
                Select::make('dateperiod_id')
                    ->label('Period Code')
                    ->options(
                        DatePeriod::query()
                            ->orderBy('id', 'desc')
                            ->pluck('code', 'code')
                    )
                    ->searchable()
                    ->default(session('session_periodcode')) // Auto-inject active session period code
                    ->required(),

                // 💡 NEW: Replaced single date picker with structured time tracking ranges
                DateTimePicker::make('timein')
                    ->label('Time In Stamp')
                    ->native(false)
                    ->required(),

                DateTimePicker::make('timeout')
                    ->label('Time Out Stamp')
                    ->native(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('holiday.type')
                    ->label('Holiday')
                    ->sortable()
                    ->searchable(),

                // 💡 FIXED: Updated column path to point directly to employeeDetails relationship 
                TextColumn::make('employeeDetails.lastname')
                    ->label('Employee')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->employeeDetails?->lastname . ', ' . $record->employeeDetails?->firstname
                    )
                    ->sortable()
                    ->searchable(
                        query: fn($query, $search) =>
                        $query->whereHas(
                            'employeeDetails', // Maps model function
                            fn($q) =>
                            $q->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                        )
                    ),

                TextColumn::make('dateperiod_id')
                    ->label('Period Code')
                    ->sortable()
                    ->searchable(),

                // 💡 NEW: Datetime metrics display matching standard formats
                TextColumn::make('timein')
                    ->label('Logged In')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('timeout')
                    ->label('Logged Out')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
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

                SelectFilter::make('holidayid')
                    ->label('Holiday')
                    ->options(
                        DB::table('holidays')->pluck('type', 'id')
                    ),

                SelectFilter::make('dateperiod_id')
                    ->label('Period Code')
                    ->options(
                        DatePeriod::query()->pluck('code', 'code')
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label('Update'),
                    DeleteAction::make()->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
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
            // 'create' => CreateHolidayLogs::route('/create'),
            // 'edit' => EditHolidayLogs::route('/{record}/edit'),
        ];
    }
}
