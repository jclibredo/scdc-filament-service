<?php

namespace App\Filament\Resources\Skills;

use App\Filament\Resources\Skills\Pages\ListSkills;
use App\Models\ActivityLog;
use App\Models\Skill;
use App\Models\User;
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

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Utility Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FolderPlus;

    protected static ?string $recordTitleAttribute = 'Skill';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        // If $user is an integer (ID), fetch the actual User model from the database
        if (is_int($user)) {
            $user = User::find($user);
        }
        // Check if we have a valid User model instance now
        if (! $user instanceof User) {
            return false;
        }
        return $user->userPermissions()
            ->whereIn('module', ['SUPERADMIN', 'HR'])
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        // return SkillForm::configure($schema);
        return $schema
            ->schema([
                Section::make('Record Details')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->columnSpanFull()
                    ->description('Provide a descriptive title and any relevant supplemental details.')
                    ->icon('heroicon-o-information-circle') // Optional: Sleek info circle icon
                    ->schema([

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 50);",
                                'maxlength' => 50,
                            ]),

                        Textarea::make('details')
                            ->label('Details')
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 100);",
                                'maxlength' => 100,
                            ])
                            ->rows(3),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        // return SkillsTable::configure($table);
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->query(function () {
                $user = Auth::user();
                if (!$user) {
                    return Skill::whereRaw('1 = 0');
                }
                return Skill::where('status', true); // Add this line
            })
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
                        ->visible(fn($record) => !TransactionCheckService::hasSkillTransactions($record))
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Updated skill details: {$record->title} (ID: {$record->id})",
                                'module'    => 'Skill Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->label('Update'),
                    DeleteAction::make()
                        ->after(function ($record) {
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deleted skill: {$record->title} (ID: {$record->id})",
                                'module'    => 'Skill Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        })
                        ->visible(fn($record) => !TransactionCheckService::hasSkillTransactions($record))
                        ->label('Remove'),

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
                            // 1. Log the deactivation activity
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deactivated skill due to active transactions: {$record->title} (ID: {$record->id})",
                                'module'    => 'Skill Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                            // 2. Trigger toaster notification
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasSkillTransactions($record) && ($record->status === true || $record->status == 1)),
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
            'index' => ListSkills::route('/'),
            // 'create' => CreateSkill::route('/create'),
            // 'edit' => EditSkill::route('/{record}/edit'),
        ];
    }
}
