<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\CreateHoliday;
use App\Filament\Resources\Holidays\Pages\EditHoliday;
use App\Filament\Resources\Holidays\Pages\ListHolidays;
use App\Filament\Resources\Holidays\Schemas\HolidayForm;
use App\Filament\Resources\Holidays\Tables\HolidaysTable;
use App\Models\Holiday;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Holiday';

    public static function form(Schema $schema): Schema
    {
        // return HolidayForm::configure($schema);
        return $schema
            ->schema([
                TextInput::make('type')
                    ->label('Type')
                    // ->options([
                    //     'public' => 'Public Holiday',
                    //     'religious' => 'Religious Holiday',
                    //     'optional' => 'Optional Holiday',
                    // ])
                    ->required(),
                TextInput::make('percentage')
                    ->label('Percentage')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
                Textarea::make('details')
                    ->label('Details')
                    ->rows(3)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return HolidaysTable::configure($table);
        return $table
            ->columns([
                TextColumn::make('type')->label('Type')->sortable()->searchable(),
                TextColumn::make('percentage')->label('Percentage')->suffix('%')->sortable(),
                TextColumn::make('details')->label('Details')->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
            'index' => ListHolidays::route('/'),
            'create' => CreateHoliday::route('/create'),
            'edit' => EditHoliday::route('/{record}/edit'),
        ];
    }
}
