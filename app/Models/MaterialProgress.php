<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialProgress extends Model
{
    protected $table = 'material_progress';

    protected $fillable = [
        'user_id',
        'material_id',
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

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
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
        if (!$this->material->duration_seconds) {
            return $this->completed ? 100 : 0;
        }

        $percentage = ($this->watched_seconds / $this->material->duration_seconds) * 100;

        return min(100, (int) round($percentage));
    }
}
