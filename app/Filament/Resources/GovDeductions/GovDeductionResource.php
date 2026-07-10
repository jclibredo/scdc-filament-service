<?php

namespace App\Filament\Resources\GovDeductions;

use App\Filament\Resources\GovDeductions\Pages\ListGovDeductions;
use App\Models\GovDeduction;
use App\Models\User;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class GovDeductionResource extends Resource
{
    protected static ?string $model = GovDeduction::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Utility Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static ?string $recordTitleAttribute = 'Deduction';

    protected static ?string $navigationLabel = 'Mandatory Deductions';
    protected static ?string $pluralModelLabel = 'Mandatory Deductions Data';

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
        return $schema->schema([
            Section::make('Project Details')
                ->columnSpanFull()
                ->extraAttributes([
                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                ])
                ->description('Enter the timeline, budget allocations, and status details.')
                ->icon('heroicon-o-briefcase') // Optional: Business/Project briefcase icon
                ->columns(2) // Creates a balanced 2-column grid layout
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->extraInputAttributes([
                            // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                            'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 25);",
                            'maxlength' => 25,
                        ])
                        ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                        ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)),

                    DatePicker::make('date_started')
                        ->label('Date Started')
                        ->default(now()) // Sets default to today
                        ->required(),

                    DatePicker::make('date_ended')
                        ->label('Date Ended')
                        ->default(now()) // Sets default to today
                        ->required(),

                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric() // Ensures only numbers/decimals are allowed
                        ->prefix('PHP')
                        ->default('0.00') // Sets the default value to 0.00
                        ->required(),

                    Toggle::make('status')
                        ->label('Active')
                        ->default(true),
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
                    return GovDeduction::whereRaw('1 = 0');
                }
                return GovDeduction::where('status', true); // Add this line
            })
            ->columns([
                // TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('date_started')->label('Start Date')->date('M d, Y')->sortable(),
                TextColumn::make('date_ended')->label('End Date')->date('M d, Y')->sortable(),
                TextColumn::make('amount')->label('Amount'),
                IconColumn::make('status')->boolean()->label('Active'),
                TextColumn::make('created_at')->dateTime('M d, Y')->sortable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
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
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasGovDeductionTransactions($record) && ($record->status === true || $record->status == 1)),
                    EditAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasGovDeductionTransactions($record))
                        ->label('Update'),
                    DeleteAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasGovDeductionTransactions($record))
                        ->label('Remove'),
                ])->label('Action')
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
            'index' => ListGovDeductions::route('/'),
            // 'create' => CreateGovDeduction::route('/create'),
            // 'edit' => EditGovDeduction::route('/{record}/edit'),
        ];
    }
}
