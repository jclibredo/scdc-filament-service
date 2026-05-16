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
                            ->label('Log Timestamp'),

                        Select::make('status')
                            ->options([
                                0 => 'Check-In',
                                1 => 'Check-Out',
                                4 => 'Overtime-In',
                                5 => 'Overtime-Out',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('verification_mode')
                            ->options([
                                0 => 'Password',
                                1 => 'Fingerprint',
                                2 => 'Card',
                                5 => 'Face',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('work_code')
                            ->numeric()
                            ->default(0),

                        TextInput::make('reserved')
                            ->numeric()
                            ->default(0),
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

                TextColumn::make('recorded_at')
                    ->dateTime('M d, Y h:i A')
                    ->label('Timestamp')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('verification_mode')
                    ->label('Method')
                    ->icon(fn(int $state): string => match ($state) {
                        1 => 'heroicon-m-identification',
                        5 => 'heroicon-m-user-circle',
                        default => 'heroicon-m-key',
                    })
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        1 => 'Finger',
                        2 => 'Card',
                        5 => 'Face',
                        default => 'Code',
                    }),

                TextColumn::make('created_at')
                    ->label('Imported On')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'edit' => EditAtlog::route('/{record}/edit'),
        ];
    }
}
