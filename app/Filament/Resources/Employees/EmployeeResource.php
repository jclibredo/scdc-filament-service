<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Jobs\ProcessEmployeeCsv;
use App\Models\Employee;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::FingerPrint;

    protected static ?string $recordTitleAttribute = 'Employee';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('employeeid')
                    ->label('Employee ID')
                    ->unique(ignoreRecord: true),
                TextInput::make('firstname')->required()->maxLength(255),
                TextInput::make('middlename')->maxLength(255),
                TextInput::make('lastname')->required()->maxLength(255),
                Toggle::make('status')->label('Active')->default(true),
                TextInput::make('mobile')->maxLength(20),
                TextInput::make('email')->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                DatePicker::make('birthdate'),
                Select::make('sex')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                        'Other' => 'Other',
                    ])
                    ->required(),
                Textarea::make('address')->rows(3),
                DatePicker::make('datehired'),
                DatePicker::make('dateseperated'),
                Select::make('employeetype')
                    ->options([
                        'SM' => 'Semi-monthly',
                        'W' => 'Weekly',
                    ])
                    ->required(),
                Select::make('skill_id')
                    ->label('Skill')
                    ->relationship('skill', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employeeid')->sortable(),
                TextColumn::make('firstname')->sortable()->searchable(),
                TextColumn::make('middlename')->sortable(),
                TextColumn::make('lastname')->sortable()->searchable(),
                TextColumn::make('projectHistories.employeetype')
                    ->label('Employee Type')
                    ->formatStateUsing(function ($state) {
                        if ($state === 'SM') {
                            return 'Semi-Monthly';
                        }
                        if ($state === 'W') {
                            return 'Weekly';
                        }
                        return $state; // fallback
                    })
                    ->sortable()
                    ->searchable(),
                IconColumn::make('status')->boolean()->label('Active'),
                TextColumn::make('mobile'),
                TextColumn::make('email'),
                TextColumn::make('skill.title')->label('Skill'),
                TextColumn::make('project.name')->label('Project'),
                TextColumn::make('datehired')->date(),
                TextColumn::make('dateseperated')->date(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('upload_employee')
                    ->label('Upload Employees')
                    ->button()
                    ->form([
                        FileUpload::make('uploadfile')
                            ->label('Employee CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv'])
                            ->disk('public')
                            ->directory('employees'), // Stores in storage/app/public/employees
                    ])
                    ->action(function (array $data) {
                        // Handle uploaded CSV import logic here
                        $file = $data['uploadfile'];
                        ProcessEmployeeCsv::dispatch($file);
                        Notification::make()
                            ->title('CSV Queued for Processing')
                            ->body('The CSV file will be processed shortly.')
                            ->success()
                            ->send();
                    }),
            ])
        ;
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
        ];
    }
}
