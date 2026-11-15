<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_category_id', 'name', 'slug', 'short_description', 'description', 'featured_image',
        'base_price', 'pricing_method', 'duration_minutes', 'is_active', 'is_featured', 'sort_order',
        'included', 'benefits', 'faqs', 'seo_title', 'seo_description',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'included' => 'array',
            'benefits' => 'array',
            'faqs' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null;
    }

    /** "Question|Answer" lines stored in the faqs JSON column, as structured pairs. */
    public function faqPairs(): array
    {
        return collect($this->faqs ?? [])->map(function ($line) {
            [$q, $a] = array_pad(explode('|', (string) $line, 2), 2, '');

            return ['q' => trim($q), 'a' => trim($a)];
        })->filter(fn ($p) => $p['q'] !== '' && $p['a'] !== '')->values()->all();
    }
}
