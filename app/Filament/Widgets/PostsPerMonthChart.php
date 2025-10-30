<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PostsPerMonthChart extends ChartWidget
{
    protected ?string $heading = 'Data project site analytics';
    // protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // 🔹 Example: fetch data from real posts
        $data = DB::table('posts')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // 🔹 If no posts exist, provide some mock test data
        if (empty($data)) {
            $data = [
                1 => 5,
                2 => 8,
                3 => 3,
                4 => 10,
                5 => 6,
                6 => 12,
                7 => 7,
                8 => 4,
                9 => 9,
                10 => 15,
                11 => 11,
                12 => 13,
            ];
        }

        $labels = [];
        $totals = [];

        foreach (range(1, 12) as $month) {
            $labels[] = Carbon::create()->month($month)->format('M');
            $totals[] = $data[$month] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Posts',
                    'data' => $totals,
                    'backgroundColor' => '#3b82f6',
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
