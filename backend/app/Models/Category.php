<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * Resolve a route-bound category by primary key or by slug.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where('id', (int) $value)
            ->orWhere('slug', $value)
            ->first();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Book, $this>
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_categories');
    }

    /**
     * Collect the ids of every descendant of this category, including itself.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $pending = $this->children()->pluck('id')->all();

        while ($pending !== []) {
            $ids = array_merge($ids, $pending);
            $pending = self::query()->whereIn('parent_id', $pending)->pluck('id')->all();
        }

        return $ids;
    }
}
