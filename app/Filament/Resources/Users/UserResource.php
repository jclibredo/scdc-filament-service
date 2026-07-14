<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'User';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;
    protected  static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $recordTitleAttribute = 'User';
    protected static ?string $pluralModelLabel = 'User Account';

    public static function canViewAny(): bool
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
            ->where('module', 'SUPERADMIN')
            ->exists();
    }
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
            ->where('module', 'SUPERADMIN')
            ->exists();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('User Account Details')
                ->columnSpanFull()
                ->extraAttributes([
                    'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                ])
                ->description('Manage user credentials, profile information, application roles, and system access status.')
                ->icon('heroicon-o-user-circle') // Optional: Sleek user profile icon
                ->columns(2) // Organizes fields into a clean two-column grid layout
                ->schema([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->extraInputAttributes([
                            'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 150);",
                            'maxlength' => 150,
                        ]),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Hidden::make('password')
                        // Generate a secure random string as a fallback default placeholder
                        ->default(fn() => \Illuminate\Support\Str::random(32))
                        // Securely hash the value before saving it to the database
                        ->dehydrateStateUsing(fn($state) => \Illuminate\Support\Facades\Hash::make($state))
                        // Ensure it is only injected into the database on the 'create' action 
                        ->dehydrated(fn(string $context) => $context === 'create'),
                    Hidden::make('role')
                        ->default('admin'),
                    Hidden::make('status')
                        ->default(false),

                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        // return UsersTable::configure($table);
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (!$user) {
                    return User::whereRaw('1 = 0');
                }
                // return User::where('status', true)
                //     ->whereNot('id', $user->id);
                return User::whereNot('id', $user->id)
                    ->orderBy('name', 'asc');
            })
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                // 👇 Show active/inactive status
                IconColumn::make('status')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')->dateTime('M d, Y'),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update')
                        ->visible(fn($record) => !TransactionCheckService::hasUserTransactions($record))
                        ->after(function ($record) {
                            // Logs the update activity after the user details are successfully saved
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Updated user account: {$record->email} (ID: {$record->id})",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        }),
                    DeleteAction::make()
                        ->label('Remove')
                        ->visible(fn($record) => !TransactionCheckService::hasUserTransactions($record))
                        ->after(function ($record) {
                            // Logs the deletion activity before the model is completely flushed from memory
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deleted user account: {$record->email} (ID: {$record->id})",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                        }),

                    Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate User')
                        ->modalDescription('This user has system activity logs and cannot be deleted. Deactivating their account will restrict login access. Proceed?')
                        ->modalSubmitActionLabel('Yes, deactivate user')
                        ->action(function ($record) {
                            // 1. Change user account status to inactive
                            $record->status = false;
                            $record->save();
                            // 2. Log the deactivation activity
                            ActivityLog::create([
                                'user_id'   => Auth::id() ?? 'System',
                                'activity'  => "Deactivated user due to active system logs: {$record->name} (Email: {$record->email}, ID: {$record->id})",
                                'module'    => 'User Management',
                                'ipaddress' => request()->ip(),
                                'windows'   => request()->userAgent(),
                            ]);
                            // 3. Trigger toaster toast notification confirmation
                            Notification::make()
                                ->title('User successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if they HAVE logs AND are currently active
                        ->visible(fn($record) => TransactionCheckService::hasUserTransactions($record) && ($record->status === true || $record->status == 1)),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->color('success')
                    ->button()
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
            'index' => ListUsers::route('/'),
        ];
    }
}
