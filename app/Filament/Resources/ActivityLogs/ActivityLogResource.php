<?php

namespace App\Filament\Resources\ActivityLogs;

// use App\Filament\Resources\ActivityLogs\Pages\CreateActivityLog;
// use App\Filament\Resources\ActivityLogs\Pages\EditActivityLog;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
// use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
// use App\Filament\Resources\ActivityLogs\Schemas\ActivityLogForm;
// use App\Filament\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
// use App\Filament\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected  static string|UnitEnum|null $navigationGroup = 'Activity';
    protected static ?string $recordTitleAttribute = 'User Activity Logs';
    protected static ?string $pluralModelLabel = 'Logs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->description('Comprehensive tracking details for this specific system event.')
                    ->icon('heroicon-o-shield-check')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->schema([
                        TextInput::make('user_id')
                            ->label('Performed By')
                            ->formatStateUsing(function ($record) {
                                // If the relation exists, display the name. Otherwise, fall back to the raw ID/System string.
                                return $record->user?->name ?? $record->user_id;
                            }),
                        TextInput::make('module')
                            ->label('Module / Feature'),
                        TextInput::make('activity')
                            ->label('Activity Description')
                            ->columnSpanFull(),
                        TextInput::make('ipaddress')
                            ->label('IP Address'),
                        TextInput::make('windows')
                            ->label('OS / Environment'),
                        DateTimePicker::make('created_at')
                            ->label('Logged At'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->query(function () {
                $user = Auth::user();

                // If $user is an integer (ID), fetch the actual User model from the database
                if (is_int($user)) {
                    $user = User::find($user);
                }

                // Safety check: if user is missing or not authenticated properly, return empty
                if (! $user instanceof User) {
                    return ActivityLog::whereRaw('1 = 0');
                }

                // Initialize the query with your default sorting layout
                $query = ActivityLog::orderBy('module', 'asc');

                // Check if the user has SUPERADMIN permissions
                $isSuperAdmin = $user->userPermissions()
                    ->where('module', 'SUPERADMIN')
                    ->exists();

                // If NOT a Superadmin, restrict the query scope strictly to their own logs
                if (!$isSuperAdmin) {
                    $query->where('user_id', $user->id);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('user_id')
                    ->label('User Full Name')
                    ->formatStateUsing(fn($record) => $record->user?->name ?? $record->user_id)
                    ->searchable(query: function ($query, string $search) {
                        $query->where('user_id', 'like', "%{$search}%") // Searches raw text like 'System' or raw string IDs
                            ->orWhereHas('user', function ($q) use ($search) {
                                // If using standard Laravel name column:
                                $q->where('users.name', 'like', "%{$search}%");
                            });
                    }),
                TextColumn::make('module')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity')
                    ->label('Activity')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('ipaddress')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // 1. Filter by Module (Dynamically drops down distinct modules used in your logs)
                SelectFilter::make('module')
                    ->label('Filter by Module')
                    ->options(fn() => ActivityLog::distinct()->pluck('module', 'module')->toArray())
                    ->searchable(),

                // 2. Filter by User
                SelectFilter::make('user_id')
                    ->label('Filter by User')
                    ->relationship('user', 'name') // Pulls from your 'user' relation setup
                    ->searchable()
                    ->preload(),

                // 3. Filter by Windows (OS / Environment String)
                SelectFilter::make('windows')
                    ->label('Filter by Environment')
                    ->options(fn() => ActivityLog::distinct()->whereNotNull('windows')->pluck('windows', 'windows')->toArray())
                    ->searchable(),

                // 4. Filter by Created At Date Range
                Filter::make('created_at')
                    ->label('Filter by Logged Date')
                    ->form([
                        DatePicker::make('created_from')->label('Logged From'),
                        DatePicker::make('created_until')->label('Logged Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(), // Only allow viewing details
            ])
            ->bulkActions([
                // Empty to disable mass deletion or modifications
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
            'index' => ListActivityLogs::route('/'),
            // 'create' => CreateActivityLog::route('/create'),
            // 'view' => ViewActivityLog::route('/{record}'),
            // 'edit' => EditActivityLog::route('/{record}/edit'),
        ];
    }
}
