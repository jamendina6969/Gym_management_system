<div class="space-y-4">
    {{-- Tabs & Actions --}}
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex gap-1.5">
            @foreach (['equipment' => 'Equipment & Maintenance', 'products' => 'Products & Stock', 'sales' => 'Retail Sales'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')"
                        class="px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ $tab === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex gap-2">
            @if ($canManageEquipment)
                <button wire:click="openEquipmentModal" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">+ Add Equipment</button>
            @endif
            @if ($canManageInventory)
                <button wire:click="openProductModal" class="px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">+ Add Product</button>
            @endif
        </div>
    </div>

    {{-- EQUIPMENT TAB --}}
    @if ($tab === 'equipment')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Equipment</th>
                        <th class="px-5 py-3 text-left">Branch</th>
                        <th class="px-5 py-3 text-center">Condition</th>
                        <th class="px-5 py-3 text-left">Maintenance</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($equipment as $item)
                        <tr>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $item->name }}</div>
                                <div class="text-slate-500">{{ $item->category ?: 'General' }} {{ $item->serial_number ? '· SN: ' . $item->serial_number : '' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $item->branch->name }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full font-medium {{ $item->condition === 'good' ? 'bg-emerald-100 text-emerald-800' : ($item->condition === 'needs_maintenance' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">
                                    {{ str($item->condition)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500">
                                <div>Last: {{ $item->last_maintained_at?->format('M d, Y') ?? 'Never' }} ({{ $item->maintenance_logs_count }} logs)</div>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                @if ($canManageEquipment)
                                    <button wire:click="openMaintenanceModal({{ $item->id }})" class="text-emerald-700 hover:underline font-medium">Log Service</button>
                                    <button wire:click="openEquipmentModal({{ $item->id }})" class="text-slate-600 hover:underline font-medium">Edit</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No gym equipment tracked yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- PRODUCTS TAB --}}
    @if ($tab === 'products')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Product</th>
                        <th class="px-5 py-3 text-left">Branch</th>
                        <th class="px-5 py-3 text-right">Price</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                                <div class="text-slate-500">{{ $product->category ?: 'Retail' }} {{ $product->sku ? '· ' . $product->sku : '' }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $product->branch->name }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $product->price, 2) }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full font-semibold {{ $product->isLowStock() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $product->stock_quantity }} {{ $product->isLowStock() ? '(Low)' : '' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                @if ($canManageInventory)
                                    <button wire:click="openSaleModal({{ $product->id }})" class="px-2.5 py-1 rounded bg-emerald-600 text-white font-medium hover:bg-emerald-700">Sell</button>
                                    <button wire:click="restockProduct({{ $product->id }}, 10)" class="text-slate-600 hover:underline font-medium">+10</button>
                                    <button wire:click="openProductModal({{ $product->id }})" class="text-slate-600 hover:underline font-medium">Edit</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No retail products added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- SALES TAB --}}
    @if ($tab === 'sales')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Product</th>
                        <th class="px-5 py-3 text-center">Qty</th>
                        <th class="px-5 py-3 text-left">Method</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="px-5 py-3.5 text-slate-500">{{ $sale->sold_at->format('M d, Y h:i A') }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $sale->product->name }}</td>
                            <td class="px-5 py-3.5 text-center">{{ $sale->quantity }}</td>
                            <td class="px-5 py-3.5 uppercase text-slate-600">{{ $sale->payment_method }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ $currencySymbol }}{{ number_format((float) $sale->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No retail sales recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Equipment Modal --}}
    @if ($showEquipmentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between items-center"><h3 class="font-semibold">{{ $editingEquipmentId ? 'Edit Equipment' : 'Add Equipment' }}</h3><button wire:click="$set('showEquipmentModal', false)">✕</button></div>
                <form wire:submit="saveEquipment" class="p-6 space-y-3.5 text-sm">
                    <div><label class="block text-xs font-medium mb-1">Name *</label><input type="text" wire:model="eq_name" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium mb-1">Branch *</label><select wire:model="eq_branch_id" class="w-full rounded-lg border-slate-300 text-sm">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                        <div><label class="block text-xs font-medium mb-1">Category</label><input type="text" wire:model="eq_category" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium mb-1">Serial #</label><input type="text" wire:model="eq_serial" class="w-full rounded-lg border-slate-300 text-sm"></div>
                        <div><label class="block text-xs font-medium mb-1">Condition</label><select wire:model="eq_condition" class="w-full rounded-lg border-slate-300 text-sm"><option value="good">Good</option><option value="needs_maintenance">Needs Maintenance</option><option value="out_of_order">Out of Order</option></select></div>
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2"><button type="button" wire:click="$set('showEquipmentModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button><button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save</button></div>
                </form>
            </div>
        </div>
    @endif

    {{-- Maintenance Modal --}}
    @if ($showMaintenanceModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between items-center"><h3 class="font-semibold">Log Equipment Maintenance</h3><button wire:click="$set('showMaintenanceModal', false)">✕</button></div>
                <form wire:submit="saveMaintenance" class="p-6 space-y-3.5 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium mb-1">Date *</label><input type="date" wire:model="maint_date" class="w-full rounded-lg border-slate-300 text-sm"></div>
                        <div><label class="block text-xs font-medium mb-1">Cost ({{ $currencySymbol }})</label><input type="number" step="0.01" wire:model="maint_cost" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    </div>
                    <div><label class="block text-xs font-medium mb-1">Issue / Inspection *</label><input type="text" wire:model="maint_issue" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Action Taken</label><input type="text" wire:model="maint_action" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div><label class="block text-xs font-medium mb-1">Updated Condition</label><select wire:model="maint_new_condition" class="w-full rounded-lg border-slate-300 text-sm"><option value="good">Good</option><option value="needs_maintenance">Needs Maintenance</option><option value="out_of_order">Out of Order</option></select></div>
                    <div class="pt-3 border-t flex justify-end gap-2"><button type="button" wire:click="$set('showMaintenanceModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button><button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save Log</button></div>
                </form>
            </div>
        </div>
    @endif

    {{-- Product Modal --}}
    @if ($showProductModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between items-center"><h3 class="font-semibold">{{ $editingProductId ? 'Edit Product' : 'Add Product' }}</h3><button wire:click="$set('showProductModal', false)">✕</button></div>
                <form wire:submit="saveProduct" class="p-6 space-y-3.5 text-sm">
                    <div><label class="block text-xs font-medium mb-1">Product Name *</label><input type="text" wire:model="prod_name" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs font-medium mb-1">Branch *</label><select wire:model="prod_branch_id" class="w-full rounded-lg border-slate-300 text-sm">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                        <div><label class="block text-xs font-medium mb-1">Category</label><input type="text" wire:model="prod_category" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="block text-xs font-medium mb-1">Price ({{ $currencySymbol }})</label><input type="number" step="0.01" wire:model="prod_price" class="w-full rounded-lg border-slate-300 text-sm"></div>
                        <div><label class="block text-xs font-medium mb-1">Stock</label><input type="number" wire:model="prod_stock" min="0" class="w-full rounded-lg border-slate-300 text-sm"></div>
                        <div><label class="block text-xs font-medium mb-1">Low Alert</label><input type="number" wire:model="prod_threshold" min="0" class="w-full rounded-lg border-slate-300 text-sm"></div>
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2"><button type="button" wire:click="$set('showProductModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button><button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save</button></div>
                </form>
            </div>
        </div>
    @endif

    {{-- Sale Modal --}}
    @if ($showSaleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between items-center"><h3 class="font-semibold">Record Retail Sale</h3><button wire:click="$set('showSaleModal', false)">✕</button></div>
                <form wire:submit="recordSale" class="p-6 space-y-3.5 text-sm">
                    <div><label class="block text-xs font-medium mb-1">Quantity *</label><input type="number" wire:model="sale_quantity" min="1" class="w-full rounded-lg border-slate-300 text-sm">@error('sale_quantity') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror</div>
                    <div><label class="block text-xs font-medium mb-1">Payment Method</label><select wire:model="sale_method" class="w-full rounded-lg border-slate-300 text-sm"><option value="cash">Cash</option><option value="gcash">GCash</option><option value="maya">Maya</option><option value="card">Card</option></select></div>
                    <div class="pt-3 border-t flex justify-end gap-2"><button type="button" wire:click="$set('showSaleModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button><button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Complete Sale</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
