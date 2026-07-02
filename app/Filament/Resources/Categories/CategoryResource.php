<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Services\TransactionCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'Earnings Category';
    protected static ?string $navigationLabel = 'System Categories';
    protected static ?string $pluralModelLabel = 'System Category Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Category Details')
                    ->columnSpanFull()
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
                    ])
                    ->schema([
                        // Arranging layout items into two columns
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 30);",
                                'maxlength' => 30,
                            ]),
                        Select::make('cat')
                            ->label('Category Type')
                            ->options([
                                'PAYROLL' => 'Payroll',
                                'EARNINGS' => 'Earnings',
                                'ADJUSTMENT' => 'Adjustment',
                                'DEDUCTION' => 'Deduction',
                                'EMPLOYEE_STATUS' => 'Employee Status',
                                'EMPLOYEE_TYPE' => 'Employee Type',
                                'SUBCON' => 'Sub-Contractor Details'
                            ])
                            // 💡 Optional: Makes it searchable if your options list grows later
                            ->searchable()
                            ->placeholder('Select category type')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->extraInputAttributes([
                                // Added 0-9 to the regex character validation layout to permit numeric inputs safely
                                'oninput' => "this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '')
                            .toUpperCase().replace(/^\\s+/, '').slice(0, 100);",
                                'maxlength' => 100,
                            ])
                            ->columnSpanFull(), // Stretches out over full block width
                        // 💡 FIXED: Changed from ToggleColumn to Toggle
                        Toggle::make('status')
                            ->label('Active Status')
                            ->default(true),
                    ])
                    ->columns(2), // Switches internal grid distribution to 2 columns
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
                    return Category::whereRaw('1 = 0');
                }
                // Eager load the relationships
                return Category::query()
                    ->where('status', true);
            })
            ->columns([

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cat')
                    ->label('Category Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean() // Automatically turns true into a green check/circle and false into a red cross/circle
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasCategoryTransactions($record))
                        ->label('Update'),
                    DeleteAction::make()
                        ->visible(fn($record) => !TransactionCheckService::hasCategoryTransactions($record))
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
                            Notification::make()
                                ->title('Record successfully deactivated.')
                                ->warning()
                                ->send();
                        })
                        // 👁️ Only visible if it has transactions AND is currently active
                        ->visible(fn($record) => TransactionCheckService::hasCategoryTransactions($record) && ($record->status === true || $record->status == 1)),
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
            'index' => ListCategories::route('/'),
        ];
    }
}
