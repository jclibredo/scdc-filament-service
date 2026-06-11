<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected  static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'Earnings Category';

    protected static ?string $navigationLabel = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Category Details')
                    ->columnSpanFull()
                    ->schema([
                        // Arranging layout items into two columns
                        TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('cat')
                            ->label('Category Type')
                            ->options([
                                'PAYROLL' => 'Payroll',
                                'EARNINGS' => 'Earnings',
                                'ADJUSTMENT' => 'Adjustment',
                                'DEDUCTION' => 'Deduction',
                                'EMPLOYEE_STATUS' => 'Employee Status',
                                'EMPLOYEE_TYPE' => 'Employee Type',
                            ])
                            // 💡 Optional: Makes it searchable if your options list grows later
                            ->searchable()
                            ->placeholder('Select category type')
                            ->required(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
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
            ->recordUrl(null)
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
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->outlined()
                    ->color('warning'),
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
            // 'create' => CreateCategory::route('/create'),
            // 'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
