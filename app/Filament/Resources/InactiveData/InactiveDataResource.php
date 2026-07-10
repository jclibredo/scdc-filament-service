<?php

namespace App\Filament\Resources\InactiveData;

// use App\Filament\Resources\InactiveData\Pages\ManageInactiveData;
// use App\Filament\Resources\InactiveData\Pages\EditInactiveData;
// use App\Filament\Resources\InactiveData\Pages\ListInactiveData;
use App\Filament\Resources\InactiveDataResource\Pages\ManageInactiveData as PagesManageInactiveData;
// use App\Filament\Resources\InactiveData\Schemas\InactiveDataForm;
// use App\Filament\Resources\InactiveData\Tables\InactiveDataTable;
use App\Models\Employee;
use App\Models\User;
// use App\Models\InactiveData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
// use Filament\Forms\Components\Builder;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
// use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

// use Illuminate\Database\Query\Builder;

class InactiveDataResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected  static string|UnitEnum|null $navigationGroup = 'Archive Management';

    // 🏷️ This changes the text displayed in the Sidebar Navigation link
    protected static ?string $navigationLabel = 'Inactive Records';
    protected static ?string $pluralModelLabel = 'Archived Non-Active Data';
    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;
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
    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->columns([
                TextColumn::make('module_type')
                    ->label('Module')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn($record) => class_basename($record)),

                TextColumn::make('data_details')
                    ->label('Data Details')
                    ->getStateUsing(function ($record) {
                        return match (class_basename($record)) {
                            'Employee' => "[$record->employeeid] " . ($record->fullname ?? $record->name),
                            'Project'  => "[$record->code] $record->name",
                            'Skill', 'Category', 'GovDeduction', 'OtherDeduction' => $record->name ?? $record->title ?? "ID: $record->id",
                            default    => "ID: " . $record->getKey(),
                        };
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn() => 'Inactive'),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label('Select Module')
                    ->options([
                        'Employee'       => 'Employee',
                        'Project'        => 'Project',
                        'Skill'          => 'Skill',
                        'GovDeduction'   => 'Gov Deduction',
                        'OtherDeduction' => 'Other Deduction',
                        'Category'       => 'Category',
                        'Earnings'       => 'Earnings',
                        'Adjustment'     => 'Adjustment',
                        'Holiday'       => 'Holiday',
                    ])
                    // 💡 Tell Filament to do absolutely nothing when applying this filter to SQL
                    ->query(fn($query) => $query),
            ])
            ->actions([
                Action::make('activate')
                    ->label('Activate')
                    ->button()
                    ->outlined()
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = true;
                        $record->save();

                        Notification::make()
                            ->title('Record successfully restored to active status.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_selected')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Reactivate Selected Records')
                        ->modalDescription('Are you sure you want to restore all selected records back to an active status?')
                        ->action(function (Collection $records) {
                            // 🔄 Efficiently loop through and activate each selected model item
                            $records->each(function ($record) {
                                $record->status = true;
                                $record->save();
                            });

                            Notification::make()
                                ->title('Selected records successfully restored to active status.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => PagesManageInactiveData::route('/'),
        ];
    }
}
