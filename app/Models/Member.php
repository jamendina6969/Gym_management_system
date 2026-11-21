<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'home_branch_id',
        'member_code',
        'qr_token',
        'date_of_birth',
        'gender',
        'address',
        'photo_path',
        'emergency_contact_name',
        'emergency_contact_phone',
        'health_notes',
        'waiver_accepted_at',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'waiver_accepted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->qr_token)) {
                $member->qr_token = (string) Str::uuid();
            }
            if (empty($member->member_code)) {
                $member->member_code = static::generateMemberCode();
            }
        });
    }

    public static function generateMemberCode(): string
    {
        $year = now()->format('Y');
        $prefix = "IP-{$year}-";
        $latest = static::withTrashed()
            ->where('member_code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('member_code');

        $next = 1;
        if ($latest && preg_match('/(\d+)$/', $latest, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->latest('starts_at');
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->latest('ends_at');
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class)->latest('checked_in_at');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('booked_at');
    }

    public function approve(User $staff): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $staff->id,
        ]);
    }

    public function canAccessBranch(Branch $branch): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $subscription = $this->activeSubscription()->with('plan')->first();
        if (! $subscription) {
            return false;
        }

        if ($subscription->plan->access_scope === 'all_access') {
            return true;
        }

        return (int) $this->home_branch_id === (int) $branch->id
            || (int) $subscription->branch_id === (int) $branch->id;
    }

    public function syncStatusFromSubscriptions(): void
    {
        if ($this->status === 'suspended') {
            return;
        }

        $hasActive = $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->exists();

        if ($hasActive) {
            $this->update(['status' => 'active']);

            return;
        }

        $hasFrozen = $this->subscriptions()->where('status', 'frozen')->exists();
        if ($hasFrozen) {
            $this->update(['status' => 'frozen']);

            return;
        }

        $hasAnyPast = $this->subscriptions()
            ->whereIn('status', ['active', 'expired'])
            ->exists();

        if ($hasAnyPast) {
            $this->update(['status' => 'expired']);
        }
    }
}
