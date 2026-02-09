<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'topic_id',
        'title',
        'slug',
        'description',
        'type',
        'video_path',
        'video_filename',
        'video_size_bytes',
        'duration_seconds',
        'thumbnail_path',
        'pdf_path',
        'pdf_filename',
        'pdf_size_bytes',
        'pdf_pages',
        'order_column',
        'is_preview',
        'status',
    ];

    protected $casts = [
        'type' => 'string',
        'video_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'pdf_size_bytes' => 'integer',
        'pdf_pages' => 'integer',
        'order_column' => 'integer',
        'is_preview' => 'boolean',
    ];

    // Relationships
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }

    // Scopes
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopePdfs($query)
    {
        return $query->where('type', 'pdf');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Helper methods
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isPdf(): bool
    {
        return $this->type === 'pdf';
    }

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
        $bytes = $this->isVideo() ? $this->video_size_bytes : $this->pdf_size_bytes;

        if (!$bytes) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
