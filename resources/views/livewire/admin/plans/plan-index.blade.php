<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex flex-1 flex-col sm:flex-row gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search plans..."
                   class="w-full sm:max-w-xs rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <select wire:model.live="scopeFilter" class="rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="all">All Access Scopes</option>
                <option value="single_branch">Single Branch</option>
                <option value="all_access">All-Access (Multi-Branch)</option>
            </select>
        </div>

        @if ($canManage)
            <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                + New Plan
            </button>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($plans as $plan)
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-slate-900 text-base">{{ $plan->name }}</h3>
                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-medium {{ $plan->access_scope === 'all_access' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $plan->access_scope === 'all_access' ? 'All-Access Branches' : ($plan->branch?->name ?? 'Single Branch') }}
                            </span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $plan->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700' }}">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-baseline gap-1">
                        <span class="text-2xl font-bold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $plan->price, 2) }}</span>
                        <span class="text-xs text-slate-500">/ {{ $plan->duration_days }} days ({{ ucfirst($plan->billing_cycle) }})</span>
                    </div>

                    @if ($plan->description)
                        <p class="mt-2 text-xs text-slate-600">{{ $plan->description }}</p>
                    @endif

                    <div class="mt-3 text-xs text-slate-500 flex items-center justify-between border-t border-slate-100 pt-3">
                        <span>Class credits: <strong class="text-slate-800">{{ $plan->class_credits === null ? 'Unlimited' : $plan->class_credits }}</strong></span>
                        <span>{{ $plan->subscriptions_count }} subs</span>
                    </div>
                </div>

                @if ($canManage)
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <button wire:click="toggleActive({{ $plan->id }})" class="text-slate-600 hover:text-slate-900 font-medium">
                            {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <div class="space-x-3">
                            <button wire:click="openEditModal({{ $plan->id }})" class="text-emerald-700 hover:underline font-medium">Edit</button>
                            <button wire:click="deletePlan({{ $plan->id }})" wire:confirm="Archive {{ $plan->name }}?" class="text-rose-600 hover:underline font-medium">Archive</button>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500 text-sm">
                No membership plans found. Create your first plan to start enrolling members.
            </div>
        @endforelse
    </div>

    @if ($plans->hasPages())
        <div>{{ $plans->links() }}</div>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">{{ $editingPlanId ? 'Edit Plan' : 'Create Membership Plan' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form wire:submit="save" class="p-6 space-y-4 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Plan Name *</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. Monthly Unlimited">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Cycle *</label>
                            <select wire:model.live="billing_cycle" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Duration (Days) *</label>
                            <input type="number" wire:model="duration_days" min="1" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('duration_days') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Price ({{ $currencySymbol }}) *</label>
                            <input type="number" step="0.01" wire:model="price" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('price') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Access Scope *</label>
                            <select wire:model.live="access_scope" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="single_branch">Single Branch</option>
                                <option value="all_access">All-Access (Any Branch)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Class Credits (blank = unlimited)</label>
                            <input type="number" wire:model="class_credits" min="0" placeholder="Unlimited" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    @if ($access_scope === 'single_branch')
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Branch (blank = available to all branches as single-branch plan)</label>
                            <select wire:model="branch_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">All Branches (Local Plan)</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Active and available for signup
                    </label>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
