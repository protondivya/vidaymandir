<?php

namespace App\Filament\Widgets;

use App\Models\Book;
use Filament\Widgets\ChartWidget;

class BooksPerMonthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Books published per month';
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(function (int $offset): array {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->format('M Y'),
                'count' => Book::query()
                    ->whereNotNull('published_at')
                    ->whereBetween('published_at', [$month, $month->copy()->endOfMonth()])
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Books published',
                    'data' => $months->pluck('count')->all(),
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.15)',
                ],
            ],
            'labels' => $months->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
