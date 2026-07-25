<?php

namespace App\Filament\Resources\Assets;

// use App\Filament\Resources\Assets\Pages\CreateAsset;
// use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Page Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Asset';
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
                Section::make('Asset Profile')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Establish asset identification data, category classification, and uploaded media attachments.')
                    ->icon('heroicon-o-cube')
                    ->columns(2) // 2-column grid layout matching Project reference
                    ->schema([

                        TextInput::make('name')
                            ->label('Asset Name')
                            ->required()
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 50);",
                                'maxlength' => 50,
                            ]),
                        Select::make('category')
                            ->label('Category')
                            ->required()
                            ->options([
                                'TOOLS' => 'POWER TOOLS',
                                'VEHICLE' => 'TRANSPORT VEHICLE',
                            ])
                            ->native(false),

                        Textarea::make('details')
                            ->label('Details')
                            ->required()
                            ->rows(3)
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 255);",
                                'maxlength' => 255,
                            ])
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Asset Image')
                            ->image()
                            ->disk('public') // Saves directly to public storage disk
                            ->directory('assets') // Saved in storage/app/public/assets
                            ->maxSize(2048) // 2MB limit
                            ->columnSpanFull(),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->square(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('details')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->dateTime()
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
            'index' => ListAssets::route('/'),
            // 'create' => CreateAsset::route('/create'),
            // 'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
