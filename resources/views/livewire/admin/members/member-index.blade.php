<div class="space-y-4">
    @if ($lastInviteUrl)
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
            <div class="font-semibold">Walk-in Member Invite Link Ready</div>
            <p class="text-xs text-emerald-700 mt-0.5">Share or let the member open this link to set their own password:</p>
            <div class="mt-2 flex items-center gap-2">
                <input type="text" readonly value="{{ $lastInviteUrl }}" class="flex-1 text-xs font-mono rounded-lg border-emerald-300 bg-white text-slate-700">
                <button type="button" wire:click="$set('lastInviteUrl', null)" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">Dismiss</button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email, phone, or member ID..."
                   class="w-full sm:max-w-sm rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select wire:model.live="statusFilter" class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="pending">Pending Approval</option>
                <option value="expired">Expired</option>
                <option value="frozen">Frozen</option>
            </select>
        </div>

        @if ($canCreate)
            <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                + Add Walk-in Member
            </button>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Member</th>
                        <th class="px-5 py-3 text-left">Branch</th>
                        <th class="px-5 py-3 text-left">Current Plan</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($members as $member)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $member->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $member->member_code }} · {{ $member->user->email }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 text-xs">
                                {{ $member->homeBranch?->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs">
                                @if ($member->activeSubscription)
                                    <div class="font-medium text-slate-900">{{ $member->activeSubscription->plan->name }}</div>
                                    <div class="text-slate-500">Expires {{ $member->activeSubscription->ends_at->format('M d, Y') }}</div>
                                @else
                                    <span class="text-slate-400">No active plan</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @php
                                    $statusColor = match($member->status) {
                                        'active' => 'bg-emerald-100 text-emerald-800',
                                        'pending' => 'bg-amber-100 text-amber-800',
                                        'frozen' => 'bg-sky-100 text-sky-800',
                                        'expired' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($member->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-2">
                                @if ($member->status === 'pending' && $canApprove)
                                    <button wire:click="approveMember({{ $member->id }})" class="px-2.5 py-1 rounded-md bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                                        Approve
                                    </button>
                                @endif
                                <a href="{{ route('admin.members.show', $member) }}" class="text-slate-700 hover:text-emerald-700 font-medium text-xs">
                                    View Profile →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-slate-500">No members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($members->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">{{ $members->links() }}</div>
        @endif
    </div>

    {{-- Walk-in Member Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl overflow-hidden my-8">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Register Walk-in Member</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="createWalkInMember" class="p-6 space-y-4 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Full Name *</label>
                            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Email *</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Phone</label>
                            <input type="text" wire:model="phone" placeholder="+63 917 ..." class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Home Branch *</label>
                            <select wire:model="home_branch_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                            @error('home_branch_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Gender</label>
                            <select wire:model="gender" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Prefer not to say</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Date of Birth</label>
                            <input type="date" wire:model="date_of_birth" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Emergency Contact Name</label>
                            <input type="text" wire:model="emergency_contact_name" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Emergency Contact Phone</label>
                            <input type="text" wire:model="emergency_contact_phone" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Health / Medical Notes</label>
                        <textarea wire:model="health_notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Allergies, injuries, conditions..."></textarea>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 pt-1">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" wire:model="approve_immediately" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Approve &amp; activate immediately (ID verified)
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" wire:model="send_invite" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Email password-setup invite link
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Register Member</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
