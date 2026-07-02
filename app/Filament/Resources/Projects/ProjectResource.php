<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Employee;
use App\Models\Project;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
                Section::make('Project Profile')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Establish basic identification data, site location info, and operational availability.')
                    ->icon('heroicon-o-building-office-2') // Optional: Sleek building/office icon
                    ->columns(2) // Sets up a clean, multi-column grid layout
                    ->schema([

                        TextInput::make('project_code')
                            ->label('Project Code'),

                        TextInput::make('name')
                            ->label('Name')
                            ->required(),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(2)
                            ->columnSpanFull(), // Stretches the address field wide across its own row

                        Toggle::make('status')
                            ->label('Set project status')
                            ->default(true)
                            ->inline(false) // Aligns the toggle label beautifully over the switch component
                            ->columnSpanFull(), // Drops the toggle nicely onto its own clean row at the bottom

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
                    return Project::whereRaw('1 = 0');
                }
                return Project::where('status', true); // Add this line
            })
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
                        ->visible(fn($record) => TransactionCheckService::hasProjectTransactions($record) && ($record->status === true || $record->status == 1)),
                    EditAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasProjectTransactions($record))
                        ->label('Update'),
                    DeleteAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasProjectTransactions($record))
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
            ])
            ->headerActions([
                Action::make('importEmployees')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined()
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
