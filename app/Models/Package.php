<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'subject_id',
        'school_year',
        'topic_id',
        'price',
        'compare_at_price',
        'thumbnail_path',
        'is_featured',
        'status',
        'order_column',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'type' => 'string',
        'school_year' => 'string',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'order_column' => 'integer',
    ];

    // Relationships
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isSubjectPackage(): bool
    {
        return $this->type === 'subject';
    }

    public function isSubjectYearPackage(): bool
    {
        return $this->type === 'subject_year';
    }

    public function isTopicPackage(): bool
    {
        return $this->type === 'topic';
    }

    public function getIncludedTopics()
    {
        if ($this->isTopicPackage()) {
            return Topic::where('id', $this->topic_id)->get();
        }

        if ($this->isSubjectYearPackage()) {
            return Topic::where('subject_id', $this->subject_id)
                ->where('school_year', $this->school_year)
                ->where('status', 'published')
                ->get();
        }

        if ($this->isSubjectPackage()) {
            return Topic::where('subject_id', $this->subject_id)
                ->where('status', 'published')
                ->get();
        }

        return collect();
    }

    public function getTotalMaterialsCount(): int
    {
        return $this->getIncludedTopics()->sum('material_count');
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentage(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }
}
