<?php

namespace App\Filament\Resources;

use App\Enums\BookStatus;
use App\Filament\Resources\BookResource\Pages;
use App\Models\Book;
use App\Models\LicenseType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Books';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set, ?string $state, string $operation) => $operation === 'create' && blank($get('slug')) ? $set('slug', Str::slug($state)) : null),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(280)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL-friendly identifier. Leave blank on create to auto-generate.'),

                Textarea::make('synopsis')
                    ->label('Synopsis')
                    ->rows(4)
                    ->maxLength(5000)
                    ->columnSpanFull(),

                TextInput::make('language')
                    ->label('Language (ISO 639-1)')
                    ->maxLength(2)
                    ->default('en')
                    ->required(),

                TextInput::make('page_count')
                    ->label('Pages')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('word_count')
                    ->label('Words')
                    ->numeric()
                    ->minValue(0),

                Select::make('license_type_id')
                    ->label('License type')
                    ->relationship('licenseType', 'name')
                    ->required()
                    ->default(fn (): ?int => LicenseType::query()->value('id')),

                TextInput::make('rights_source')
                    ->label('Rights source')
                    ->maxLength(500),

                Select::make('status')
                    ->label('Status')
                    ->options(BookStatus::class)
                    ->default(BookStatus::Draft->value)
                    ->required()
                    ->live(),

                DateTimePicker::make('published_at')
                    ->label('Published at')
                    ->hidden(fn (Get $get): bool => $get('status') !== BookStatus::Active->value),

                Select::make('authors')
                    ->label('Authors')
                    ->relationship('authors', 'name')
                    ->multiple()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('bio')
                            ->label('Bio')
                            ->rows(3),
                        TextInput::make('birth_year')
                            ->label('Birth year')
                            ->numeric(),
                        TextInput::make('death_year')
                            ->label('Death year')
                            ->numeric(),
                    ]),

                Select::make('categories')
                    ->label('Categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(140),
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ]),

                FileUpload::make('pdf_file')
                    ->label('PDF file')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(102400)
                    ->directory('books')
                    ->disk('local'),

                TextInput::make('cover_image_url')
                    ->label('Cover image URL')
                    ->url()
                    ->maxLength(500)
                    ->helperText('External URL to the book cover image.'),

                Toggle::make('is_downloadable')
                    ->label('Downloadable')
                    ->default(true),

                Select::make('created_by')
                    ->label('Created by')
                    ->relationship('creator', 'display_name')
                    ->default(fn (): ?int => auth()->id()),

                TextInput::make('view_count')
                    ->label('Views')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn (Book $record): ?string => $record->authors->isEmpty()
                        ? null
                        : $record->authors->pluck('name')->implode(', ')),

                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->limitList(3),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (BookStatus $state): string => match ($state) {
                        BookStatus::Active => 'success',
                        BookStatus::Draft => 'gray',
                        BookStatus::Deactivated => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('language')
                    ->label('Lang')
                    ->sortable(),

                TextColumn::make('licenseType.name')
                    ->label('License')
                    ->toggleable(),

                ToggleColumn::make('is_downloadable')
                    ->label('Downloadable')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('hasPdf')
                    ->label('PDF')
                    ->state(fn (Book $record): bool => $record->hasPdf())
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('view_count')
                    ->label('Views')
                    ->sortable()
                    ->numeric(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(BookStatus::class),

                SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('language')
                    ->label('Language')
                    ->options(fn (): array => Book::query()
                        ->whereNotNull('language')
                        ->distinct()
                        ->pluck('language', 'language')
                        ->all()),

                TernaryFilter::make('is_downloadable')
                    ->label('Downloadable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
