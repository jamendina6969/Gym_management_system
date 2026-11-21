<div class="space-y-4">
    {{-- Toolbar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search branch name, code, or address..."
                   class="w-full sm:max-w-xs rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">

            <select wire:model.live="statusFilter"
                    class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="all">All Statuses</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>

        @if ($canManage)
            <button wire:click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Branch
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Branch</th>
                        <th class="px-5 py-3 text-left">Contact</th>
                        <th class="px-5 py-3 text-center">Staff</th>
                        <th class="px-5 py-3 text-center">Self-Signup</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        @if ($canManage)
                            <th class="px-5 py-3 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($branches as $branch)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2 py-0.5 rounded bg-slate-900 text-emerald-400 font-mono text-xs font-semibold">{{ $branch->code }}</span>
                                    <span class="font-semibold text-slate-900">{{ $branch->name }}</span>
                                </div>
                                @if ($branch->address)
                                    <div class="text-xs text-slate-500 mt-1">{{ $branch->address }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div>{{ $branch->phone ?: '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $branch->email ?: '' }}</div>
                            </td>
                            <td class="px-5 py-4 text-center font-medium text-slate-700">
                                {{ $branch->users_count }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if ($canManage)
                                    <button wire:click="toggleSelfRegistration({{ $branch->id }})"
                                            class="px-2.5 py-1 rounded-full text-xs font-medium transition {{ $branch->allow_self_registration ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                        {{ $branch->allow_self_registration ? 'Enabled' : 'Disabled' }}
                                    </button>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $branch->allow_self_registration ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $branch->allow_self_registration ? 'Enabled' : 'Disabled' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if ($canManage)
                                    <button wire:click="toggleActive({{ $branch->id }})"
                                            class="px-2.5 py-1 rounded-full text-xs font-medium transition {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $branch->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @endif
                            </td>
                            @if ($canManage)
                                <td class="px-5 py-4 text-right whitespace-nowrap space-x-2">
                                    <button wire:click="openEditModal({{ $branch->id }})"
                                            class="text-slate-600 hover:text-emerald-700 font-medium text-xs">
                                        Edit
                                    </button>
                                    <button wire:click="deleteBranch({{ $branch->id }})"
                                            wire:confirm="Archive {{ $branch->name }}? Existing records remain intact."
                                            class="text-rose-600 hover:text-rose-800 font-medium text-xs">
                                        Archive
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-10 text-center text-slate-500">
                                No branches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($branches->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $branches->links() }}
            </div>
        @endif
    </div>

    {{-- Create / Edit Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ $editingBranchId ? 'Edit Branch' : 'Add New Branch' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-6 space-y-4 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Branch Name *</label>
                            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. BGC Taguig Branch">
                            @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Code *</label>
                            <input type="text" wire:model="code" class="w-full rounded-lg border-slate-300 text-sm uppercase font-mono focus:border-emerald-500 focus:ring-emerald-500" placeholder="BGC">
                            @error('code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Address</label>
                        <textarea wire:model="address" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Full street address"></textarea>
                        @error('address') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Phone</label>
                            <input type="text" wire:model="phone" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="+63 917 ...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="branch@ironpulse.gym">
                            @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Mon–Fri</label>
                            <input type="text" wire:model="hoursMonFri" class="w-full rounded-lg border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Saturday</label>
                            <input type="text" wire:model="hoursSat" class="w-full rounded-lg border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Sunday</label>
                            <input type="text" wire:model="hoursSun" class="w-full rounded-lg border-slate-300 text-xs focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 pt-1">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" wire:model="allow_self_registration" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Allow online member self-signup
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Branch is active
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                            {{ $editingBranchId ? 'Save Changes' : 'Create Branch' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
