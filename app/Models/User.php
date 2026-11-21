<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_path',
        'is_active',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['super-admin', 'branch-manager', 'staff', 'trainer']);
    }

    public function isMember(): bool
    {
        return $this->hasRole('member');
    }

    /**
     * Branches this user is allowed to manage/view in the admin panel.
     *
     * @return Collection<int, Branch>
     */
    public function accessibleBranches(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Branch::query()->active()->orderBy('name')->get();
        }

        return $this->branches()->where('branches.is_active', true)->orderBy('name')->get();
    }

    public function primaryBranch(): ?Branch
    {
        $primary = $this->branches()->wherePivot('is_primary', true)->first();

        return $primary ?? $this->accessibleBranches()->first();
    }
}
