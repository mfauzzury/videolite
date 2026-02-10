<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    protected $table = 'video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'watched_seconds',
        'completed',
        'completed_at',
        'last_position_seconds',
    ];

    protected $casts = [
        'watched_seconds' => 'integer',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_position_seconds' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    // Helper methods
    public function markAsCompleted(): void
    {
        $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function getProgressPercentage(): int
    {
        if (!$this->video->duration_seconds) {
            return $this->completed ? 100 : 0;
        }

        $percentage = ($this->watched_seconds / $this->video->duration_seconds) * 100;

        return min(100, (int) round($percentage));
    }

    public function updateProgress(int $watchedSeconds, int $lastPosition): void
    {
        $this->update([
            'watched_seconds' => $watchedSeconds,
            'last_position_seconds' => $lastPosition,
        ]);

        // Auto-mark as completed if watched 95% or more
        if ($this->getProgressPercentage() >= 95 && !$this->completed) {
            $this->markAsCompleted();
        }
    }
}
