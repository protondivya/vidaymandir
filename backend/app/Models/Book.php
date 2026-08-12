<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookStatus;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'language',
        'page_count',
        'word_count',
        'cover_image_url',
        'pdf_file',
        'is_downloadable',
        'license_type_id',
        'rights_source',
        'status',
        'view_count',
        'created_by',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookStatus::class,
            'page_count' => 'integer',
            'word_count' => 'integer',
            'view_count' => 'integer',
            'is_downloadable' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Resolve a route-bound book by primary key or by slug.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where('id', (int) $value)
            ->orWhere('slug', $value)
            ->first();
    }

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_authors')
            ->withPivot('sort_order')
            ->orderBy('book_authors.sort_order');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_categories');
    }

    /**
     * @return BelongsTo<LicenseType, $this>
     */
    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope the query to publicly visible (active) books only.
     *
     * @param  Builder<Book>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BookStatus::Active);
    }

    /**
     * Whether the book has a stored PDF file on disk.
     */
    public function hasPdf(): bool
    {
        return $this->pdf_file !== null
            && Storage::disk('local')->exists($this->pdf_file);
    }

    /**
     * The absolute path to the stored PDF file, or null when absent.
     */
    public function pdfPath(): ?string
    {
        return $this->hasPdf() ? Storage::disk('local')->path($this->pdf_file) : null;
    }

    /**
     * Generate a unique URL slug from the given title.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
