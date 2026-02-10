<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'section_id',
        'course_id',
        'title',
        'description',
        'type',
        'video_path',
        'video_filename',
        'video_size_bytes',
        'duration_seconds',
        'pdf_path',
        'pdf_filename',
        'article_content',
        'order_column',
    ];

    protected $casts = [
        'video_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'order_column' => 'integer',
    ];

    // Relationships
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Helper methods
    public function hasVideo(): bool
    {
        return !empty($this->video_path);
    }

    public function hasPdf(): bool
    {
        return !empty($this->pdf_path);
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
}
