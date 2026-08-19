<?php

namespace App\Filament\Resources\AboutUs;

use App\Filament\Resources\AboutUs\Pages\ListAboutUs;
use App\Models\AboutUs;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AboutUsResource extends Resource
{
    protected static ?string $model = AboutUs::class;

    protected  static string|UnitEnum|null $navigationGroup = 'Page Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // protected static ?string $recordTitleAttribute = 'AboutUs';
    protected static ?string $pluralModelLabel = 'About-us';

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
            ->whereIn('module', ['SUPERADMIN', 'CMS'])
            ->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Company Overview')
                    ->description('Manage core details, vision, and mission statement.')
                    ->columns(1)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->schema([
                        Textarea::make('company_details')
                            ->label('Company Details')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('mission')
                            ->label('Mission Statement')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('vision')
                            ->rows(3)
                            ->label('Vision Statement')
                            ->columnSpanFull(),

                        FileUpload::make('coverimage')
                            ->label('Cover Image')
                            ->image()
                            ->disk('public')
                            ->directory('about-us')
                            ->imageEditor()
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->columns([
                ImageColumn::make('coverimage')
                    ->label('Cover')
                    ->disk('public')
                    ->square(),

                TextColumn::make('company_details')
                    ->label('Details')
                    ->html()
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('mission')
                    ->label('Mission')
                    ->limit(40),

                TextColumn::make('vision')
                    ->label('Vision')
                    ->limit(40),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('success')
                    ->size('xs')
                    ->outlined(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListAboutUs::route('/'),
            // 'create' => CreateAboutUs::route('/create'),
            // 'edit' => EditAboutUs::route('/{record}/edit'),
        ];
    }
}
