<?php

namespace App\Filament\Resources\Homes;


use App\Filament\Resources\Homes\Pages\ListHomes;
use App\Models\Home;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class HomeResource extends Resource
{
    protected static ?string $model = Home::class;

    protected  static string|UnitEnum|null $navigationGroup = 'Page Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $pluralModelLabel = 'Home';

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
                Section::make('Hero & Section Content')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('title')
                            ->label('Section Title')
                            ->required()
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Media Assets')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('bigimage')
                            ->label('Main / Big Image')
                            ->image()
                            ->disk('public')
                            ->directory('home/big')
                            ->imageEditor()
                            ->maxSize(10240) // 10MB
                            ->helperText('Upload the primary hero banner image.'),

                        FileUpload::make('smallimage')
                            ->label('Small / Supporting Gallery Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('home/gallery')
                            ->maxFiles(10)
                            ->maxSize(5120) // 5MB per file
                            ->helperText('Upload up to 10 thumbnail/gallery images.'),
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
                ImageColumn::make('bigimage')
                    ->label('Big Image')
                    ->disk('public')
                    ->square(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->html()
                    ->limit(60),

                ImageColumn::make('smallimage')
                    ->label('Gallery')
                    ->disk('public')
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(),

                TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->sortable(),
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
            'index' => ListHomes::route('/'),
            // 'create' => CreateHome::route('/create'),
            // 'edit' => EditHome::route('/{record}/edit'),
        ];
    }
}
