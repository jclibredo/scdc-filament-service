<?php

namespace App\Filament\Resources\Events;

// use App\Filament\Resources\Events\Pages\CreateEvent;
// use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
// use App\Filament\Resources\Events\Schemas\EventForm;
// use App\Filament\Resources\Events\Tables\EventsTable;
use App\Models\Event;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected  static string|UnitEnum|null $navigationGroup = 'Page Management';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Event';
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
                Section::make('Event Details')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->description('Manage event metadata, schedules, descriptive content, and gallery attachments.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2) // 2-column layout matching Project reference
                    ->schema([

                        TextInput::make('title')
                            ->label('Event Title')
                            ->required()
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 100);",
                                'maxlength' => 100,
                            ]),

                        DatePicker::make('date')
                            ->label('Event Date')
                            ->required()
                            ->native(false), // Custom Filament date picker widget

                        Textarea::make('details')
                            ->label('Details')
                            ->required()
                            ->rows(3)
                            ->extraInputAttributes([
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s.-]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 500);",
                                'maxlength' => 500,
                            ])
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Event Images')
                            ->image()
                            ->multiple() // Allows uploading multiple image files
                            ->reorderable()
                            ->disk('public') // Saves to storage/app/public/events
                            ->directory('events')
                            ->maxSize(2048) // 2MB limit per image
                            ->columnSpanFull(),

                        Toggle::make('status')
                            ->label('Set event status')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Images')
                    ->disk('public')
                    ->circular()
                    ->stacked() // Displays multiple uploaded images stacked on top of each other
                    ->limit(3),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('details')
                    ->limit(50),

                IconColumn::make('status')
                    ->boolean(),

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
            'index' => ListEvents::route('/'),
            // 'create' => CreateEvent::route('/create'),
            // 'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
