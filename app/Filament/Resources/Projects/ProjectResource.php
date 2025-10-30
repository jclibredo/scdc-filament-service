<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'Project';

    public static function form(Schema $schema): Schema
    {
        // return ProjectForm::configure($schema);
        return $schema
            ->schema([
                TextInput::make('project_code')
                    ->label('Project Code')
                    ->disabled() // makes it uneditable in Filament
                    ->dehydrated() // still sends value when saving
                    ->default(fn() => strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))),
                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                Textarea::make('address')
                    ->label('Address'),

                Toggle::make('status')
                    ->label('Set project status')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project_code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('address')->limit(30),
                IconColumn::make('status')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListProjects::route('/'),
            // 'create' => CreateProject::route('/create'),
            // 'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
