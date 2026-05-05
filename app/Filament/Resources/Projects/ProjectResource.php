<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Employee;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
                    ->label('Project Code'),
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
            ->recordUrl(null)
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
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ]),
            ])
            // ->bulkActions([
            //     DeleteBulkAction::make(),
            // ])
            ->headerActions([
                Action::make('importEmployees')
                    ->label('Import Employees CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('csv_file')
                            ->label('Upload CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv'])
                            ->directory('imports'),
                    ])
                    ->action(function (array $data): void {
                        $path = storage_path('app/' . $data['csv_file']);
                        if (!file_exists($path)) {
                            Notification::make()
                                ->title('File not found!')
                                ->danger()
                                ->send();
                            return;
                        }

                        $handle = fopen($path, 'r');
                        $header = fgetcsv($handle); // Get column headers

                        while (($row = fgetcsv($handle)) !== false) {
                            $data = array_combine($header, $row);
                            // Adjust these keys to match your Employee model fields
                            Employee::create([
                                'project_code' => $data['project_code'] ?? null,
                                'name' => $data['name'] ?? null,
                                'address' => $data['address'] ?? null,
                                'status' => isset($data['status']) ? (bool)$data['status'] : true,
                            ]);
                        }

                        fclose($handle);

                        Notification::make()
                            ->title('Employees imported successfully!')
                            ->success()
                            ->send();
                    })
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
