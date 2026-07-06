<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PostsPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Payroll Monthly Overview';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Fetch real aggregated metrics per month from your model table
        $data = DB::table('payroll_summary_reports')
            ->selectRaw('
                MONTH(created_at) as month, 
                SUM(grosspay) as total_gross, 
                SUM(totalnetpay) as total_net
            ')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $labels = [];
        $grossTotals = [];
        $netTotals = [];

        // Loop through all 12 months to build uniform datasets
        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::create()->month($month)->format('M');

            // Fallback to mock figures if no data exists for development testing
            if (empty($data)) {
                $grossTotals[] = match ($month) {
                    1 => 50000,
                    2 => 55000,
                    3 => 48000,
                    4 => 62000,
                    5 => 58000,
                    6 => 70000,
                    7 => 65000,
                    8 => 60000,
                    9 => 63000,
                    10 => 75000,
                    11 => 72000,
                    12 => 85000,
                };
                $netTotals[] = match ($month) {
                    1 => 42000,
                    2 => 46000,
                    3 => 40000,
                    4 => 53000,
                    5 => 49000,
                    6 => 60000,
                    7 => 55000,
                    8 => 51000,
                    9 => 54000,
                    10 => 64000,
                    11 => 61000,
                    12 => 73000,
                };
            } else {
                $grossTotals[] = $data[$month]->total_gross ?? 0;
                $netTotals[] = $data[$month]->total_net ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net Pay (Line)',
                    'data' => $netTotals,
                    'type' => 'line', // Forces this specific dataset to render as a line
                    'borderColor' => '#10b981', // Emerald green
                    'backgroundColor' => '#10b981',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.3, // Adds a slight smooth curve to the line
                ],
                [
                    'label' => 'Gross Pay (Bar)',
                    'data' => $grossTotals,
                    'type' => 'bar', // Explicitly renders as a bar
                    'backgroundColor' => '#3b82f6', // Blue
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // The base type remains 'bar' so Chart.js initializes the coordinate grid correctly
        return 'bar';
    }
}
