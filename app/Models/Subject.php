<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Subject extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail_path',
        'icon',
        'color',
        'status',
        'order_column',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'status' => 'string',
        'order_column' => 'integer',
    ];

    // Relationships
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('school_year')->orderBy('order_column');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column');
    }

    // Helper methods
    public function getTopicsByYear(string $year)
    {
        return $this->topics()->where('school_year', $year)->get();
    }

    public function getPublishedTopicsCount(): int
    {
        return $this->topics()->where('status', 'published')->count();
    }

    // Activity logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('subject');
    }
}
