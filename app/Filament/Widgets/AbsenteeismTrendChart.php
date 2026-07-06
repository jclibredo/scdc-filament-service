<?php

namespace App\Filament\HiddenWidgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AbsenteeismTrendChart extends ChartWidget
{
    protected static bool $shouldRegister = false;
    protected ?string $heading = 'Monthly Absenteeism Trend';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // 1. Fetch count of absents ('A') grouped by month from date_entry
        $data = DB::table('payroll_reports')
            ->selectRaw('MONTH(date_entry) as month, COUNT(*) as total_absences')
            ->where('paytype', 'A')
            ->whereYear('date_entry', now()->year)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $labels = [];
        $absentTotals = [];

        // 2. Map data to all 12 months uniformly
        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::create()->month($month)->format('M');

            // Provides visual mock data for testing if no DB records match yet
            if (empty($data)) {
                $absentTotals[] = match ($month) {
                    1 => 12,
                    2 => 8,
                    3 => 15,
                    4 => 5,
                    5 => 9,
                    6 => 22,
                    7 => 14,
                    8 => 7,
                    9 => 11,
                    10 => 4,
                    11 => 13,
                    12 => 19,
                };
            } else {
                $absentTotals[] = $data[$month]->total_absences ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Absences',
                    'data' => $absentTotals,
                    'borderColor' => '#ef4444', // Red trend line for alerts/absences
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)', // Translucent fill under the line
                    'borderWidth' => 3,
                    'fill' => true, // Fills the area beneath the line
                    'tension' => 0.3, // Smooth bezier curves
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
