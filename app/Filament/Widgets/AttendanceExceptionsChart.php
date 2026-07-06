<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AttendanceExceptionsChart extends ChartWidget
{
    protected ?string $heading = 'Attendance Exceptions: Absences vs. Lateness';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $currentYear = now()->year;

        // 1. Fetch Absences Count ('A') per month
        $absentsData = DB::table('payroll_reports')
            ->selectRaw('MONTH(date_entry) as month, COUNT(*) as total_absences')
            ->where('paytype', 'A')
            ->whereYear('date_entry', $currentYear)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        // 2. Fetch Late/Undertime Incidents Count (> 0) per month
        $latenessData = DB::table('payroll_reports')
            ->selectRaw('MONTH(date_entry) as month, COUNT(*) as total_incidents')
            ->where('late_undertime', '>', 0)
            ->whereYear('date_entry', $currentYear)
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();

        $labels = [];
        $absentTotals = [];
        $lateTotals = [];

        // 3. Map both datasets to all 12 months uniformly
        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::create()->month($month)->format('M');

            // Check if database has data, otherwise load matching mock records for development
            if (empty($absentsData) && empty($latenessData)) {
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
                $absentTotals[] = $absentsData[$month]->total_absences ?? 0;
                $lateTotals[] = $latenessData[$month]->total_incidents ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Absences (Line)',
                    'data' => $absentTotals,
                    'type' => 'line', // Overrides base type to render as line
                    'borderColor' => '#ef4444', // Red
                    'backgroundColor' => '#ef4444',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Late/Undertime Incidents (Bar)',
                    'data' => $lateTotals,
                    'type' => 'bar', // Explicitly renders as a bar
                    'backgroundColor' => '#f59e0b', // Amber/Orange
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // Must return 'bar' at the chart level so the coordinate system handles bars cleanly
        return 'bar';
    }
}
