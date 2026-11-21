<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'membership_plan_id',
        'branch_id',
        'starts_at',
        'ends_at',
        'price',
        'amount_paid',
        'remaining_class_credits',
        'status',
        'frozen_at',
        'frozen_until',
        'cancelled_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'frozen_at' => 'date',
            'frozen_until' => 'date',
            'cancelled_at' => 'datetime',
            'price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_class_credits' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balance(): float
    {
        return max(0, round((float) $this->price - (float) $this->amount_paid, 2));
    }

    public function isFullyPaid(): bool
    {
        return $this->balance() <= 0;
    }

    public function daysRemaining(): int
    {
        if ($this->ends_at->isPast()) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->ends_at->startOfDay());
    }

    public function freeze(?Carbon $until = null): void
    {
        $this->update([
            'status' => 'frozen',
            'frozen_at' => now()->toDateString(),
            'frozen_until' => $until?->toDateString(),
        ]);

        $this->member->syncStatusFromSubscriptions();
    }

    public function unfreeze(): void
    {
        $extraDays = 0;
        if ($this->frozen_at) {
            $extraDays = (int) $this->frozen_at->diffInDays(now()->startOfDay());
        }

        $this->update([
            'status' => 'active',
            'ends_at' => $this->ends_at->copy()->addDays($extraDays)->toDateString(),
            'frozen_at' => null,
            'frozen_until' => null,
        ]);

        $this->member->syncStatusFromSubscriptions();
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->member->syncStatusFromSubscriptions();
    }

    public function recalculatePayments(): void
    {
        $paidTotal = (float) $this->payments()->where('status', 'paid')->sum('amount');

        $updates = ['amount_paid' => $paidTotal];

        if ($this->status === 'pending' && $paidTotal > 0) {
            $updates['status'] = 'active';
        }

        $this->update($updates);
        $this->member->syncStatusFromSubscriptions();
    }
}
