<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BooksPerMonthChart;
use App\Filament\Widgets\RecentUsersWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopBooksWidget;
use App\Filament\Widgets\ViewsByCategoryChart;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.pages.reports';

    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            BooksPerMonthChart::class,
            ViewsByCategoryChart::class,
            TopBooksWidget::class,
            RecentUsersWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
