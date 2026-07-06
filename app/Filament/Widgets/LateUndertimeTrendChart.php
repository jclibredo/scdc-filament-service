<?php

namespace App\Filament\HiddenWidgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LateUndertimeTrendChart extends ChartWidget
{
    protected static bool $shouldRegister = false;
    protected ?string $heading = 'Monthly Late / Undertime Incidents';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // 1. Fetch count of late/undertime occurrences (> 0) grouped by month
        $data = DB::table('payroll_reports')
            ->selectRaw('MONTH(date_entry) as month, COUNT(*) as total_incidents')
            ->where('late_undertime', '>', 0)
            ->whereYear('date_entry', now()->year)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $labels = [];
        $lateTotals = [];

        // 2. Map data to all 12 months uniformly
        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::create()->month($month)->format('M');

            // Provides visual mock data for testing if no DB records exist yet
            if (empty($data)) {
                $lateTotals[] = match ($month) {
                    1 => 25,
                    2 => 18,
                    3 => 30,
                    4 => 12,
                    5 => 15,
                    6 => 28,
                    7 => 22,
                    8 => 14,
                    9 => 19,
                    10 => 9,
                    11 => 24,
                    12 => 35,
                };
            } else {
                $lateTotals[] = $data[$month]->total_incidents ?? 0;
            }
        }

        return [
            // 'datasets' => [
            //     [
            //         'label' => 'Late/Undertime Occurrences',
            //         'data' => $lateTotals,
            //         'borderColor' => '#f59e0b', // Amber/Orange color scheme for warning indicators
            //         'backgroundColor' => 'rgba(245, 158, 11, 0.1)', // Light amber fill area
            //         'borderWidth' => 3,
            //         'fill' => true,
            //         'tension' => 0.3, // Smooth curved lines
            //     ],
            // ],
            // 'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
