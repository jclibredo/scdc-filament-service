<?php

namespace App\Filament\Resources\OtherDeductions;

use App\Filament\Resources\OtherDeductions\Pages\ListOtherDeductions;
use App\Models\OtherDeduction;
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

class OtherDeductionResource extends Resource
{
    protected static ?string $model = OtherDeduction::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Utility Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::MinusCircle;

    protected static ?string $recordTitleAttribute = 'OtherDeduction';
    protected static ?string $navigationLabel = 'Other Deductions';
    protected static ?string $pluralModelLabel = 'Other Deductions Category Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('General Information')
                ->columnSpanFull()
                ->extraAttributes([
                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                ])
                ->description('Provide a concise title and a detailed description for this record.')
                ->icon('heroicon-o-document-text') // Optional: Document icon for text entry
                ->schema([

                    TextInput::make('title')
                        ->label('Title')
                        ->extraInputAttributes([
                            // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                            'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 30);",
                            'maxlength' => 30,
                        ])
                        ->required(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->nullable(),

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
                    return OtherDeduction::whereRaw('1 = 0');
                }
                return OtherDeduction::where('status', true); // Add this line
            })
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->label('Description'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
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
                        ->visible(fn($record) => TransactionCheckService::hasOtherDeductionTransactions($record) && ($record->status === true || $record->status == 1)),
                    EditAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasOtherDeductionTransactions($record))
                        ->label('Update'),
                    DeleteAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasOtherDeductionTransactions($record))
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
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
            'index' => ListOtherDeductions::route('/'),
        ];
    }
}
