<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_class_id',
        'branch_id',
        'trainer_id',
        'room',
        'starts_at',
        'ends_at',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function confirmedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->whereIn('status', ['confirmed', 'attended']);
    }

    public function waitlistedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->where('status', 'waitlisted')
            ->orderBy('waitlist_position')
            ->orderBy('id');
    }

    public function spotsLeft(): int
    {
        $confirmed = $this->confirmed_bookings_count ?? $this->confirmedBookings()->count();

        return max(0, $this->capacity - $confirmed);
    }

    public function isFull(): bool
    {
        return $this->spotsLeft() <= 0;
    }
}
