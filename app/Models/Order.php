<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'package_id',
        'subtotal',
        'total_amount',
        'payment_method',
        'payment_status',
        'billplz_bill_id',
        'billplz_url',
        'billplz_paid_at',
        'billplz_transaction_id',
        'customer_name',
        'customer_email',
        'ip_address',
        'user_agent',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billplz_paid_at' => 'datetime',
        'paid_at' => 'datetime',
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

    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class);
    }

    // Helper methods
    public static function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return "ORD-{$date}-{$random}";
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->payment_status === 'failed';
    }

    public function markAsPaid(string $transactionId = null, $paidAt = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'billplz_transaction_id' => $transactionId,
            'billplz_paid_at' => $paidAt,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }
}
