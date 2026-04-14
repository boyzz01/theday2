<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'cover_image_path',
        'status', 'published_at', 'author_name', 'category_id',
        'meta_title', 'meta_description', 'canonical_url', 'og_image_path',
        'featured', 'reading_time',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function getReadingTimeAttribute(): int
    {
        if ($this->attributes['reading_time']) {
            return (int) $this->attributes['reading_time'];
        }
        // ~200 words per minute
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        return max(1, (int) ceil($wordCount / 200));
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image_path) return null;
        return asset('storage/' . $this->cover_image_path);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        $path = $this->og_image_path ?? $this->cover_image_path;
        if (!$path) return null;
        return asset('storage/' . $path);
    }
}
