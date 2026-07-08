<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(string $context): bool => $context === 'create')
                        ->maxLength(255),
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
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
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
