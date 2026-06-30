<?php

namespace App\Filament\Resources\GovDeductions;

use App\Filament\Resources\GovDeductions\Pages\ListGovDeductions;
use App\Models\GovDeduction;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GovDeductionResource extends Resource
{
    protected static ?string $model = GovDeduction::class;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPathRoundedSquare;

    protected static ?string $recordTitleAttribute = 'Deduction';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Project Details')
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
                        ->maxLength(255)
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
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
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
