<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryItem extends Model
{
    protected $fillable = ['title', 'category', 'description', 'before_image', 'after_image', 'is_active', 'sort_order'];

    protected $appends = ['before_url', 'after_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getBeforeUrlAttribute(): ?string
    {
        return $this->before_image ? Storage::disk('public')->url($this->before_image) : null;
    }

    public function getAfterUrlAttribute(): ?string
    {
        return $this->after_image ? Storage::disk('public')->url($this->after_image) : null;
    }
}
