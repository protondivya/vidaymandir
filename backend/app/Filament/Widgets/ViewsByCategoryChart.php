<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ViewsByCategoryChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Book views by category';
    }

    protected function getData(): array
    {
        $rows = DB::table('book_categories')
            ->join('categories', 'categories.id', '=', 'book_categories.category_id')
            ->join('books', 'books.id', '=', 'book_categories.book_id')
            ->select('categories.name', DB::raw('SUM(books.view_count) as total_views'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_views')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $rows->pluck('total_views')->map(fn ($value) => (int) $value)->all(),
                    'backgroundColor' => [
                        '#4f46e5',
                        '#7c3aed',
                        '#2563eb',
                        '#0891b2',
                        '#059669',
                        '#65a30d',
                        '#ca8a04',
                        '#ea580c',
                        '#dc2626',
                        '#be185d',
                    ],
                ],
            ],
            'labels' => $rows->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
