<?php

namespace App\Filament\Resources\IncentiveBonuses;

use App\Filament\Resources\IncentiveBonuses\Pages\CreateIncentiveBonus;
use App\Filament\Resources\IncentiveBonuses\Pages\EditIncentiveBonus;
use App\Filament\Resources\IncentiveBonuses\Pages\ListIncentiveBonuses;
use App\Filament\Resources\IncentiveBonuses\Schemas\IncentiveBonusForm;
use App\Filament\Resources\IncentiveBonuses\Tables\IncentiveBonusesTable;
use App\Models\Category;
use App\Models\Employee;
use App\Models\IncentiveBonus;
use App\Models\YearEndReport;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class IncentiveBonusResource extends Resource
{
    protected static ?string $model = IncentiveBonus::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'IncentiveBonus';


    public static function table(Table $table): Table
    {
        $yearendid     = session('session_yearendreportspid');
        $sessionType    = session('session_employeetypeid');
        $sessionStatus  = session('session_employeestatusid');
        $sessionReptype  = session('session_reptype');
        // dd('YEAR END ID : '.$yearendid, ' EMP TYPE : '.$sessionType, ' EMP STATUS : '.$sessionStatus, ' REPORT TYPE : '.$sessionReptype);
        if (!$yearendid || !$sessionStatus || !$sessionType) {
            return $table;
        }
        $yearenddetails = cache()->remember(
            "header_admission_full_{$yearendid}",
            3600,
            function () use ($yearendid) {
                return YearEndReport::where('code', $yearendid)
                    ->where('status', true)
                    ->first();
            }
        );
        $emtype = $yearenddetails?->employeeTypeCategory?->name ?? 'N/A';
        $emstat = $yearenddetails?->category?->name ?? 'N/A';
        $startdate = $yearenddetails->datefrom
            ? Carbon::parse($yearenddetails->datefrom)->format('M d, Y') : 'N/A';
        $enddate = $yearenddetails->dateto
            ? Carbon::parse($yearenddetails->dateto)->format('M d, Y') : 'N/A';

        $partnerSession = session('session_partnersid');
        $reportsType = ($sessionReptype === '13THMONTH' ? '13th Month Reports' : 'Incentives Reports');
        $details = [
            "DATE START: {$startdate}",
            "DATE END: {$enddate}",
            "EMP TYPE: {$emtype}",
            "EMP STATUS: {$emstat}",
            "REPORT TYPE: {$reportsType}",
        ];
        if ($partnerSession === 'ALL') {
            $details[] = "SUBCON NAME : ALL";
        } elseif ($partnerSession !== '0' && !empty($partnerSession)) {
            $subconName = Category::where('id', $partnerSession)->value('name');
            $details[] = "SUBCON NAME : " . ($subconName ? strtoupper($subconName) : 'N/A');
        }
        $formattedBadges = collect($details)
            ->map(fn($detail) => "
                    <span style='
                        padding: 0.25rem 0.625rem; 
                        font-size: 0.75rem; 
                        font-weight: 600; 
                        background-color: #ffffff; 
                        color: #374151; 
                        border-radius: 0.375rem; 
                        border: 1px solid #e5e7eb; 
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); 
                        white-space: nowrap;
                        font-family: system-ui, sans-serif;
                    '>{$detail}</span>
                ")
            ->implode(' ');
        return $table
            ->extraAttributes([
                'style' => 'border: 2px solid #2d2380 !important; border-radius: 0.75rem;', // Deep Sapphire Blue
            ])
            ->header(fn() => new HtmlString("
                    <div style='
                        padding: 1rem; 
                        margin: 1rem 1rem 0 1rem; 
                        border-inline-start: 4px solid #d97706; 
                        background-color: rgba(241, 201, 71, 0.4); 
                        border-start-end-radius: 0.75rem; 
                        border-end-end-radius: 0.75rem; 
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                    '>
                        <div style='
                            display: flex; 
                            flex-direction: column; 
                            gap: 0.75rem;
                        '>
                            <div style='display: flex; align-items: center; gap: 0.5rem;'>
                                <span style='
                                    inline-size: 0.5rem; 
                                    block-size: 0.5rem; 
                                    background-color: #f59e0b; 
                                    border-radius: 9999px;
                                '></span>
                                <h3 style='
                                    font-size: 1rem; 
                                    font-weight: 700; 
                                    color: #111827; 
                                    margin: 0;
                                    font-family: system-ui, sans-serif;
                                '>
                                    YEAR END REPORT CODE : <span style='font-family: monospace; color: #b45309;'>{$yearendid}</span>
                                </h3>
                            </div>
                            <div style='
                                display: flex; 
                                flex-wrap: wrap; 
                                align-items: center; 
                                gap: 0.5rem;
                            '>
                                {$formattedBadges}
                            </div>
                        </div>
                    </div>
                "))
            ->recordUrl(null)
            ->query(function () {
                $user = Auth::user();
                if (! $user || ! $user->id) {
                    return Employee::whereRaw('1 = 0');
                }
                // 1. Fetch the active date period
                $datePeriodData = YearEndReport::where('code', session('session_yearendreportspid'))
                    ->where('status', true)
                    ->first();
                if (! $datePeriodData) {
                    return Employee::query()->whereRaw('1 = 0');
                }
                $query = Employee::query()
                    ->where('status', true)
                    ->where('datehired', '<=', $datePeriodData->datefrom);

                if (! session('session_employeestatusid') || ! session('session_employeetypeid')) {
                    return $query;
                }
                $query->where('empstatus', session('session_employeestatusid'))
                    ->where('employeetype', session('session_employeetypeid'));
                $partnerSession = session('session_partnersid');
                if ($partnerSession && $partnerSession !== 'ALL') {
                    $query->where('partners', $partnerSession);
                }
                $project = session('session_projectid');
                if ($project && $project !== 'ALL') {
                    $query->where('project_id', $project);
                }
                return $query->orderBy('lastname');
            })
            ->columns([
                TextColumn::make('employeeid')->sortable()->searchable(),
                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('lastname', 'like', "%{$search}%")
                                ->orWhere('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('lastname', $direction)
                            ->orderBy('firstname', $direction);
                    })
                    ->formatStateUsing(function ($record) {
                        return "{$record->lastname}, {$record->firstname} {$record->middlename}";
                    }),
                TextColumn::make('empType.name')->sortable()->searchable(),
                TextColumn::make('empStat.name')
                    ->label('Emp. Status')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListIncentiveBonuses::route('/'),
            'create' => CreateIncentiveBonus::route('/create'),
            'edit' => EditIncentiveBonus::route('/{record}/edit'),
        ];
    }
}
