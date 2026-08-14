<?php

namespace App\Filament\Widgets;

use App\Enums\BookStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\ReadingProgress;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total books', Book::count())
                ->description('Books in the catalog')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Active books', Book::query()->where('status', BookStatus::Active)->count())
                ->description('Publicly visible titles')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Total users', User::count())
                ->description(fn (): string => User::query()
                    ->where('role', UserRole::Admin)
                    ->orWhere('role', UserRole::Librarian)
                    ->count().' staff members')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Categories', Category::count())
                ->description('Catalog taxonomy')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),

            Stat::make('Total views', number_format(Book::sum('view_count')))
                ->description('Cumulative book views')
                ->descriptionIcon('heroicon-m-eye')
                ->color('gray'),

            Stat::make('Active readers', ReadingProgress::query()->distinct('user_id')->count())
                ->description('Users with reading progress')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('danger'),
        ];
    }
}
