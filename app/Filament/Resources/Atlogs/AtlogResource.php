<?php

namespace App\Filament\Resources\Atlogs;

use App\Filament\Resources\Atlogs\Pages\CreateAtlog;
use App\Filament\Resources\Atlogs\Pages\EditAtlog;
use App\Filament\Resources\Atlogs\Pages\ListAtlogs;
use App\Filament\Resources\Atlogs\Schemas\AtlogForm;
use App\Filament\Resources\Atlogs\Tables\AtlogsTable;
use App\Models\Atlog;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AtlogResource extends Resource
{
    protected static ?string $model = Atlog::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Atlog';

    public static function form(Schema $schema): Schema
    {
        // return AtlogForm::configure($schema);
        return $schema
            ->schema([
                Select::make('userid')
                    ->label('Employee')
                    ->relationship('user', 'firstname') // or full name accessor
                    ->searchable()
                    ->preload()
                    ->required(),

                TimePicker::make('timein')
                    ->required(),

                TimePicker::make('timeout'),

                DateTimePicker::make('datetime')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return AtlogsTable::configure($table);
        return $table
            ->columns([
                TextColumn::make('userid'),
                TextColumn::make('timein'),
                TextColumn::make('timeout'),
                TextColumn::make('datetime')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
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
            'create' => CreateAtlog::route('/create'),
            'edit' => EditAtlog::route('/{record}/edit'),
        ];
    }
}
