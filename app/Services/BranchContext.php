<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class BranchContext
{
    public const SESSION_KEY = 'current_branch_id';

    public function initialize(?User $user = null): void
    {
        $user ??= Auth::user();

        if (! $user || ! $user->isStaff()) {
            return;
        }

        $selected = session(self::SESSION_KEY);
        $accessible = $user->accessibleBranches();

        if ($user->isSuperAdmin()) {
            // Super admin can stay in "all branches" (null) or a valid active branch.
            if ($selected !== null && ! $accessible->contains('id', (int) $selected)) {
                session()->forget(self::SESSION_KEY);
            }

            return;
        }

        if ($selected === null || ! $accessible->contains('id', (int) $selected)) {
            $primary = $user->primaryBranch();
            if ($primary) {
                session([self::SESSION_KEY => $primary->id]);
            }
        }
    }

    public function setBranch(?int $branchId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user || ! $user->isStaff()) {
            return false;
        }

        if ($branchId === null) {
            if (! $user->isSuperAdmin()) {
                return false;
            }
            session()->forget(self::SESSION_KEY);

            return true;
        }

        $accessible = $user->accessibleBranches();
        if (! $accessible->contains('id', $branchId)) {
            return false;
        }

        session([self::SESSION_KEY => $branchId]);

        return true;
    }

    public function currentBranchId(): ?int
    {
        $id = session(self::SESSION_KEY);

        return $id !== null ? (int) $id : null;
    }

    public function currentBranch(): ?Branch
    {
        $id = $this->currentBranchId();

        return $id ? Branch::find($id) : null;
    }

    /**
     * Branch IDs that queries should be scoped to for the current user and selection.
     *
     * @return array<int>
     */
    public function scopedBranchIds(?User $user = null): array
    {
        $user ??= Auth::user();

        if (! $user) {
            return [];
        }

        $currentId = $this->currentBranchId();
        if ($currentId !== null) {
            return [$currentId];
        }

        return $user->accessibleBranches()->pluck('id')->all();
    }

    /**
     * @return Collection<int, Branch>
     */
    public function accessibleBranches(?User $user = null): Collection
    {
        $user ??= Auth::user();

        return $user ? $user->accessibleBranches() : new Collection();
    }
}
