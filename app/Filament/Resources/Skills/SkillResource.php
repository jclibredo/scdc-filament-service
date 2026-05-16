<?php

namespace App\Filament\Resources\Skills;

use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\Skill;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FolderPlus;

    protected static ?string $recordTitleAttribute = 'Skill';

    public static function form(Schema $schema): Schema
    {
        // return SkillForm::configure($schema);
        return $schema
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('details')
                    ->label('Details')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        // return SkillsTable::configure($table);
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('details')->limit(50),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
            ->filters([])
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
            'index' => ListSkills::route('/'),
            // 'create' => CreateSkill::route('/create'),
            // 'edit' => EditSkill::route('/{record}/edit'),
        ];
    }
}
