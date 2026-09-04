<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\User;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected  static string|UnitEnum|null $navigationGroup = 'Utility Management';

    protected static ?string $recordTitleAttribute = 'Holiday';

    protected static ?string $navigationLabel = 'Payment Rate';
    protected static ?string $pluralModelLabel = 'Payment Type & Premium Rate Data';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        // If $user is an integer (ID), fetch the actual User model from the database
        if (is_int($user)) {
            $user = User::find($user);
        }
        // Check if we have a valid User model instance now
        if (! $user instanceof User) {
            return false;
        }
        return $user->userPermissions()
            ->whereIn('module', ['SUPERADMIN', 'HR'])
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Holiday Configuration')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Define new holiday categories, adjust standard premium rate percentages, and document computational bounds.')
                    ->icon('heroicon-o-gift') // Optional: Sleek holiday/gift icon
                    ->columns(2) // Sets up a balanced 2-column grid layout for row 1
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('type')
                            ->label('Holiday Name')
                            ->placeholder('E.G., NON-WORKING, SPECIAL')
                            ->required()
                            ->maxLength(25)
                            // 💡 FIXED REGEX: First character MUST be a letter (A-Z). Following characters can be letters, spaces, or hyphens.
                            ->regex('/^[A-Z][A-Z\s\.-]*$/')
                            ->validationMessages([
                                'regex' => 'The Holiday name must start with a letter and contain uppercase letters, spaces, and hyphens only.',
                                'max' => 'The Holiday name cannot be longer than 25 characters.',
                            ])
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^a-zA-Z\s\.-]/g, '').replace(/^\s+/g, '').toUpperCase()",
                                'style' => 'text-transform: uppercase;'
                            ]),

                        TextInput::make('percentage')
                            ->label('Holiday Percentage')
                            ->numeric()
                            ->inputMode('decimal')
                            ->placeholder('0.00')
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->validationMessages([
                                'numeric' => 'The percentage field must be a valid number.',
                                'min' => 'The percentage cannot be less than 0%.',
                                'max' => 'The percentage cannot exceed 100%.',
                            ]),

                        Textarea::make('details')
                            ->label('Computation Details')
                            ->placeholder('DESCRIBE BRACKET RANGES OR SPECIAL EXEMPTIONS HERE...')
                            ->rows(3)
                            ->required()
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 200);",
                                'maxlength' => 200,
                            ])

                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (!$user) {
                    return Holiday::whereRaw('1 = 0');
                }
                return Holiday::where('status', true); // Add this line
            })
            ->columns([
                TextColumn::make('type')->label('Type')->sortable()->searchable(),
                TextColumn::make('percentage')->label('Percentage')->suffix('%')->sortable(),
                TextColumn::make('details')->label('Details')->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Updated payment rate/holiday: {$record->type} to {$record->percentage}% (ID: {$record->id})",
                                'module'    => 'Payment Rate Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->visible(fn($record) => !TransactionCheckService::hasHolidayTransactions($record))
                        ->label('Update'),
                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation() // ⚠️ Adds the confirmation step before running
                        ->modalHeading('Deactivate Record')
                        ->modalDescription('This record has active transactions and cannot be deleted. Deactivating it will turn its status to inactive. Proceed?')
                        ->modalSubmitActionLabel('Yes, deactivate')
                        ->action(function ($record) {
                            // Deactivate the record
                            $record->status = false;
                            $record->save();
                            // 1. Log the deactivation activity
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deactivated payment rate/holiday due to active transactions: {$record->type} (ID: {$record->id})",
                                'module'    => 'Payment Rate Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasHolidayTransactions($record) && ($record->status === true || $record->status == 1)),
                    DeleteAction::make()
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deleted payment rate/holiday: {$record->type} (ID: {$record->id})",
                                'module'    => 'Payment Rate Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->visible(fn($record) => !TransactionCheckService::hasHolidayTransactions($record))
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
            ]);
        // ->bulkActions([
        //     DeleteBulkAction::make(),
        // ]);
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
            'index' => ListHolidays::route('/'),
        ];
    }
}
