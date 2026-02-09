<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'order_id',
        'status',
        'expires_at',
        'enrolled_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function canAccessMaterial(Material $material): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $package = $this->package;
        $topic = $material->topic;

        // Check if material's topic is included in the package
        if ($package->isTopicPackage()) {
            return $topic->id === $package->topic_id;
        }

        if ($package->isSubjectYearPackage()) {
            return $topic->subject_id === $package->subject_id &&
                   $topic->school_year === $package->school_year;
        }

        if ($package->isSubjectPackage()) {
            return $topic->subject_id === $package->subject_id;
        }

        return false;
    }

    public function getAccessibleTopics()
    {
        return $this->package->getIncludedTopics();
    }
}
