<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'branch_id',
        'member_id',
        'subscription_id',
        'amount',
        'currency',
        'payment_method',
        'gateway',
        'gateway_reference',
        'reference_number',
        'proof_path',
        'status',
        'paid_at',
        'recorded_by',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->invoice_number)) {
                $payment->invoice_number = static::generateInvoiceNumber();
            }
            if (empty($payment->currency)) {
                $payment->currency = config('app.currency', 'PHP');
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "INV-{$date}-";
        $latest = static::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function markAsPaid(?User $verifier = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => $this->paid_at ?? now(),
            'verified_by' => $verifier?->id ?? $this->verified_by,
        ]);

        if ($this->subscription) {
            $this->subscription->recalculatePayments();
        }

        if ($this->member && $this->member->status === 'pending') {
            $this->member->update([
                'status' => 'active',
                'approved_at' => $this->member->approved_at ?? now(),
                'approved_by' => $verifier?->id ?? $this->member->approved_by,
            ]);
        }
    }

    public function markAsRefunded(): void
    {
        $this->update(['status' => 'refunded']);

        if ($this->subscription) {
            $this->subscription->recalculatePayments();
        }
    }
}
