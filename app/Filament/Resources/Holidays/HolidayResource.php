<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Models\Holiday;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $recordTitleAttribute = 'Holiday';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('type')
                    ->label('Holiday Name')
                    ->placeholder('E.G., NON-WORKING, SPECIAL')
                    ->required()
                    ->maxLength(25)
                    // 💡 FIXED REGEX: First character MUST be a letter (A-Z). Following characters can be letters, spaces, or hyphens.
                    ->regex('/^[A-Z][A-Z\s\-]*$/')
                    ->validationMessages([
                        'regex' => 'The Holiday name must start with a letter and contain uppercase letters, spaces, and hyphens only.',
                        'max' => 'The Holiday name cannot be longer than 25 characters.',
                    ])
                    ->extraInputAttributes([
                        // 💡 FIXED JAVASCRIPT: 
                        // 1. Removes non-letters/spaces/hyphens (strips numbers, symbols etc.)
                        // 2. Removes any leading spaces dynamically as they type
                        // 3. Converts to uppercase
                        'oninput' => "this.value = this.value.replace(/[^a-zA-Z\s\-]/g, '').replace(/^\s+/g, '').toUpperCase()",
                        'style' => 'text-transform: uppercase;'
                    ])
                    ->columnSpan(1),

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
                    ])
                    ->columnSpan(1),

                Textarea::make('details')
                    ->label('Computation Details')
                    ->placeholder('DESCRIBE BRACKET RANGES OR SPECIAL EXEMPTIONS HERE...')
                    ->rows(3)
                    ->required()
                    ->maxLength(100)
                    ->regex('/^[A-Z\s\W\d_]+$/')
                    ->validationMessages([
                        'max' => 'The Computation Details cannot be longer than 100 characters.',
                    ])
                    ->extraInputAttributes([
                        'oninput' => "this.value = this.value.replace(/[0-9]/g, '').toUpperCase()",
                        'style' => 'text-transform: uppercase;'
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
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
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
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
