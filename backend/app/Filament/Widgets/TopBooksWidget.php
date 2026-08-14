<?php

namespace App\Filament\Widgets;

use App\Enums\BookStatus;
use App\Models\Book;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopBooksWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Book::query()
                    ->with(['authors', 'categories'])
                    ->orderByDesc('view_count')
                    ->limit(10),
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50)
                    ->description(fn (Book $record): ?string => $record->authors->isEmpty()
                        ? null
                        : $record->authors->pluck('name')->implode(', ')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (BookStatus $state): string => match ($state) {
                        BookStatus::Active => 'success',
                        BookStatus::Draft => 'gray',
                        BookStatus::Deactivated => 'danger',
                    }),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable()
                    ->numeric(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
