<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'name',
        'slug',
        'description',
        'school_year',
        'thumbnail_path',
        'order_column',
        'level',
        'duration_minutes',
        'material_count',
        'status',
        'published_at',
    ];

    protected $casts = [
        'school_year' => 'string',
        'order_column' => 'integer',
        'duration_minutes' => 'integer',
        'material_count' => 'integer',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('order_column');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('school_year', $year);
    }

    // Helper methods
    public function updateMaterialCount(): void
    {
        $this->update([
            'material_count' => $this->materials()->count(),
            'duration_minutes' => (int) ($this->materials()->sum('duration_seconds') / 60),
        ]);
    }

    public function getVideoCount(): int
    {
        return $this->materials()->where('type', 'video')->count();
    }

    public function getPdfCount(): int
    {
        return $this->materials()->where('type', 'pdf')->count();
    }
}
