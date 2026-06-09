<?php

namespace App\Filament\Resources\Atlogs;

use App\Filament\Resources\Atlogs\Pages\EditAtlog;
use App\Filament\Resources\Atlogs\Pages\ListAtlogs;
use App\Models\Atlog;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

class AtlogResource extends Resource
{
    protected static ?string $model = Atlog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Atlog';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Log Details')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('user_id')
                            ->label('Employee')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(
                                fn(string $search): array =>
                                Employee::where('firstname', 'like', "%{$search}%")
                                    ->orWhere('lastname', 'like', "%{$search}%")
                                    ->orWhere('employeeid', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn($item) => [$item->employeeid => "{$item->full_name} ({$item->employeeid})"])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(
                                fn($value): ?string =>
                                Employee::where('employeeid', $value)->first()?->full_name
                            ),

                        DateTimePicker::make('recorded_at')
                            ->required()
                            ->label('Date & Time'),

                        // 💡 FIXED: Aligned with your Table Column 1 (Att State)
                        Select::make('status')
                            ->label('Att State (Verification Method)')
                            ->options([
                                1 => 'Finger',
                                2 => 'Card',
                                5 => 'Face',
                                0 => 'Code', // Using 0 as fallback/default value
                            ])
                            ->required()
                            ->native(false),

                        // 💡 FIXED: Aligned with your Table Column 2 (Verify Type)
                        Select::make('verification_mode')
                            ->label('Verify Type (Attendance State)')
                            ->options([
                                0 => 'Check-In',
                                1 => 'Check-Out',
                                2 => 'Break Out',
                                3 => 'Break In',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('work_code')
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated(),

                        TextInput::make('reserved')
                            ->label('Device ID')
                            ->numeric()
                            ->default(0)
                            ->disabled(fn(string $operation): bool => $operation === 'edit')
                            ->dehydrated(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Atlog::whereRaw('1 = 0');
                }

                $sessionEmployeeId = session('session_employee_id');
                if ($sessionEmployeeId) {
                    // If no employee ID is set in the session, return an empty query
                    return Atlog::query()
                        ->where('user_id', $sessionEmployeeId);
                }
                // Eager load the relationships
                return Atlog::query()
                    ->with('employee')
                    ->with('project');
            })
            ->columns([

                TextColumn::make('project.name')
                    ->label('Project'),
                TextColumn::make('employee.full_name')
                    ->label('Employee'),

                TextColumn::make('user_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                // 2. Date & Time
                TextColumn::make('recorded_at')
                    ->dateTime('M d, Y h:i A')
                    ->label('Date & Time')
                    ->sortable(),

                // 5. Verification Method (Column 5 in your raw logs)
                TextColumn::make('status')
                    ->label('Att State')
                    ->badge()
                    ->color('info') // Cool blue badge style for hardware properties
                    ->icon(fn(int $state): string => match ($state) {
                        1 => 'heroicon-m-identification',    // Changed to actual fingerprint icon
                        2 => 'heroicon-m-credit-card',    // Changed to credit card for RFID badge
                        5 => 'heroicon-m-user-circle',    // Face scan icon
                        default => 'heroicon-m-key',      // Password/Code pin entry
                    })
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        1 => 'Finger',
                        2 => 'Card',
                        5 => 'Face',
                        default => 'Code',
                    })
                    ->sortable(),
                TextColumn::make('verification_mode')
                    ->label('Verify Type')
                    ->badge()
                    // 🎨 Using neutral gray since this is a technical hardware status code
                    ->color('gray')
                    ->icon('heroicon-m-squares-plus')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'Check-In',
                        1 => 'Check-Out',
                        2 => 'Break Out',
                        3 => 'Break In',
                        default => "Code ({$state})",
                    })
                    ->sortable(),
                // 6. Machine Terminal ID / Reserved (Column 6 in your raw logs)
                TextColumn::make('reserved')
                    ->label('Device ID')
                    ->fontFamily('mono'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        0 => 'Check-In',
                        1 => 'Check-Out',
                    ]),
                Filter::make('recorded_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('recorded_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('recorded_at', '<=', $data['until']));
                    })
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Details'),
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
            ])
            ->defaultSort('recorded_at', 'desc');
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
            // 'edit' => EditAtlog::route('/{record}/edit'),
        ];
    }
}
