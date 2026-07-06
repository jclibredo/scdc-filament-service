<?php

namespace App\Filament\Resources\YearEndReports;

use App\Filament\Resources\ThirteenthMonths\ThirteenthMonthResource;
use App\Filament\Resources\YearEndReports\Pages\CreateYearEndReport;
use App\Filament\Resources\YearEndReports\Pages\EditYearEndReport;
use App\Filament\Resources\YearEndReports\Pages\ListYearEndReports;
use App\Filament\Resources\YearEndReports\Schemas\YearEndReportForm;
use App\Filament\Resources\YearEndReports\Tables\YearEndReportsTable;
use App\Models\Category;
use App\Models\YearEndReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class YearEndReportResource extends Resource
{
    protected static ?string $model = YearEndReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Printer;
    protected  static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Year End Reports';

    public static function form(Schema $schema): Schema
    {
        return  $schema
            ->schema([
                Section::make('Year-End Report Configuration')
                    ->description('Define report parameters, employee groupings, and project assignments.')
                    ->extraAttributes([
                        'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;',
                    ])
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        // 1. Report Code
                        TextInput::make('code')
                            ->label('Code')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => strtoupper(Str::random(6)))
                            ->rules(function ($record) {
                                // If updating, ignore the row matching the current record's code
                                return [
                                    Rule::unique('date_periods', 'code')->ignore($record?->code, 'code')
                                ];
                            })
                            ->required(),
                        Select::make('rep_type')
                            ->label('Resource Type')
                            ->options([
                                'INCENTIVES' => 'Incentives',
                                '13THMONTH'  => '13th Month Pay',
                                'BONUS'      => 'Bonus',
                            ])
                            ->required()
                            ->native(false),
                        // 2. Employee Type (Linked to Categories)
                        Select::make('emptype')
                            ->label('Employee Type')
                            ->options(function () {
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_TYPE')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        // 3. Employee Status (Linked to Categories)
                        Select::make('empstatus')
                            ->label('Employee Status')
                            ->options(function () {
                                return Category::query()
                                    ->where('cat', 'EMPLOYEE_STATUS')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        // 4. Project
                        Select::make('projectid')
                            ->label('Project')
                            ->helperText('Select the project associated with this report. If no project is selected, the report will include all projects.')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        // 5. Partner (Subcontractor)
                        Select::make('partners')
                            ->label('Sub. Contractor')
                            ->options(function () {
                                return Category::query()
                                    ->where('cat', 'SUBCON')
                                    ->pluck('name', 'id')
                                    ->prepend('ALL', 'ALL');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(function (Get $get) {
                                $selectedId = $get('emptype');
                                if (! $selectedId) {
                                    return false;
                                }
                                $category = Category::find($selectedId);
                                return $category && strtoupper($category->name) === 'SUB-CON';
                            })
                            ->required(
                                fn(Get $get) => ($cat = Category::find($get('emptype'))) && strtoupper($cat->name) === 'SUB-CON'
                            ),

                        DatePicker::make('datefrom')
                            ->label('Date From')
                            ->required()
                            ->live() // Forces the form to evaluate state changes instantly
                            ->maxDate(fn(Get $get) => $get('dateto')), // Prevents selecting a 'datefrom' after 'dateto'

                        DatePicker::make('dateto')
                            ->label('Date To')
                            ->required()
                            ->disabled(fn(Get $get) => empty($get('datefrom')))
                            ->minDate(fn(Get $get) => $get('datefrom')),

                        // 6. Status Toggle
                        Toggle::make('status')
                            ->label('Active Status')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),
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
                if (! $user || ! $user->id) {
                    return YearEndReport::whereRaw('1 = 0');
                }
                $query = YearEndReport::query()
                    ->where('status', true);
                $code = session('session_yearendreportspid');
                if ($code) {
                    $query->where('code', $code);
                }
                return $query;
            })
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('rep_type')
                    ->label('Resource Type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'INCENTIVES' => 'Incentives',
                        '13THMONTH'  => '13th Month Pay',
                        'BONUS'      => 'Bonus',
                        default      => $state,
                    })
                    ->searchable()
                    ->sortable(),

                // 1. Automatically pulls the Category name through your relationship
                TextColumn::make('employeeTypeCategory.name')
                    ->label('EMP.TYPE')
                    ->sortable()
                    ->searchable(),

                // 2. Your existing category relationship
                TextColumn::make('category.name')
                    ->label('EMP.STATS')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('N/A')
                    ->searchable(),
                // 6. Date Range Columns
                TextColumn::make('datefrom')
                    ->label('Date From')
                    ->date('M d, Y') // Formats as 'Jul 02, 2026'
                    ->sortable(),

                TextColumn::make('dateto')
                    ->label('Date To')
                    ->date('M d, Y')
                    ->sortable(),

                IconColumn::make('status')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('status'),
                SelectFilter::make('emptype')
                    ->options(fn() => Category::where('cat', 'EMPLOYEE_TYPE')->pluck('name', 'name')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Update'),
                    DeleteAction::make()
                        ->label('Remove'),
                    Action::make('proceedToPayroll')
                        ->label('Process')
                        ->color('warning')
                        ->icon('heroicon-m-arrow-right-circle')
                        ->action(function (YearEndReport $record) {

                            session([
                                'session_yearendreportspid'  => $record->code,
                                'session_partnersid'         => $record->partners,
                                'session_employeetypeid'     => $record->emptype,
                                'session_employeestatusid'   => $record->empstatus,
                                'session_projectid'          => $record->projectid,
                                'session_reptype'            => $record->rep_type,
                            ]);
                            return redirect(ThirteenthMonthResource::getUrl('index'));
                        }),

                ])
                    ->label('Action')
                    ->icon('heroicon-m-chevron-down')
                    ->button()
                    ->color('warning')
                    ->size('xs')
                    ->outlined(),
            ]);
            // ->bulkActions([
            //     BulkActionGroup::make([
            //         DeleteBulkAction::make(),
            //     ]),
            // ]);
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
            'index' => ListYearEndReports::route('/'),
            // 'create' => CreateYearEndReport::route('/create'),
            // 'edit' => EditYearEndReport::route('/{record}/edit'),
        ];
    }
}
