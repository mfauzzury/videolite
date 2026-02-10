<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'title',
        'slug',
        'description',
        'video_path',
        'video_filename',
        'video_size_bytes',
        'duration_seconds',
        'thumbnail_path',
        'status',
        'order_column',
    ];

    protected $casts = [
        'video_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'order_column' => 'integer',
    ];

    // Relationships
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_video')
            ->withTimestamps();
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_video')
            ->withPivot('order_column')
            ->orderByPivot('order_column')
            ->withTimestamps();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Helpers
    public function getFormattedDuration(): string
    {
        if (!$this->duration_seconds) {
            return '0:00';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getFormattedSize(): string
    {
        if (!$this->video_size_bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($this->video_size_bytes, 1024));

        return round($this->video_size_bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
