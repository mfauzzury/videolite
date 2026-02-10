<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'name',
        'slug',
        'description',
        'thumbnail_path',
        'status',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    // Relationships
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'topic_video')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // Helper methods
    public function getVideoCount(): int
    {
        return $this->videos()->count();
    }

    public function getPublishedVideoCount(): int
    {
        return $this->videos()->where('status', 'published')->count();
    }
}
