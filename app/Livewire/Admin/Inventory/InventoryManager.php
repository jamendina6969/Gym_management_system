<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Equipment;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Models\ProductSale;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class InventoryManager extends Component
{
    public string $tab = 'equipment'; // equipment, products, sales

    // Equipment Modal
    public bool $showEquipmentModal = false;
    public ?int $editingEquipmentId = null;
    public ?int $eq_branch_id = null;
    public string $eq_name = '';
    public string $eq_category = 'Cardio';
    public string $eq_serial = '';
    public string $eq_condition = 'good';
    public string $eq_purchase_date = '';
    public string $eq_purchase_cost = '';
    public string $eq_next_maintenance = '';

    // Maintenance Log Modal
    public bool $showMaintenanceModal = false;
    public ?int $maint_equipment_id = null;
    public string $maint_date = '';
    public string $maint_issue = '';
    public string $maint_action = '';
    public string $maint_cost = '0';
    public string $maint_performer = '';
    public string $maint_new_condition = 'good';

    // Product Modal
    public bool $showProductModal = false;
    public ?int $editingProductId = null;
    public ?int $prod_branch_id = null;
    public string $prod_name = '';
    public string $prod_sku = '';
    public string $prod_category = 'Supplements';
    public string $prod_price = '0';
    public string $prod_cost = '0';
    public int $prod_stock = 0;
    public int $prod_threshold = 5;

    // Sale Modal
    public bool $showSaleModal = false;
    public ?int $sale_product_id = null;
    public int $sale_quantity = 1;
    public string $sale_method = 'cash';

    public function openEquipmentModal(?int $equipmentId = null): void
    {
        abort_unless(Auth::user()->can('equipment.manage'), 403);
        $this->resetValidation();
        $branchContext = app(BranchContext::class);

        if ($equipmentId) {
            $eq = Equipment::findOrFail($equipmentId);
            $this->editingEquipmentId = $eq->id;
            $this->eq_branch_id = $eq->branch_id;
            $this->eq_name = $eq->name;
            $this->eq_category = (string) $eq->category;
            $this->eq_serial = (string) $eq->serial_number;
            $this->eq_condition = $eq->condition;
            $this->eq_purchase_date = $eq->purchase_date?->toDateString() ?? '';
            $this->eq_purchase_cost = (string) ($eq->purchase_cost ?? '');
            $this->eq_next_maintenance = $eq->next_maintenance_at?->toDateString() ?? '';
        } else {
            $this->reset(['editingEquipmentId', 'eq_name', 'eq_serial', 'eq_purchase_date', 'eq_purchase_cost', 'eq_next_maintenance']);
            $this->eq_category = 'Cardio';
            $this->eq_condition = 'good';
            $this->eq_branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        }

        $this->showEquipmentModal = true;
    }

    public function saveEquipment(): void
    {
        abort_unless(Auth::user()->can('equipment.manage'), 403);
        $allowedBranches = app(BranchContext::class)->accessibleBranches()->pluck('id')->all();

        $validated = $this->validate([
            'eq_branch_id' => ['required', Rule::in($allowedBranches)],
            'eq_name' => ['required', 'string', 'max:120'],
            'eq_category' => ['nullable', 'string', 'max:60'],
            'eq_serial' => ['nullable', 'string', 'max:80'],
            'eq_condition' => ['required', 'in:good,needs_maintenance,out_of_order'],
            'eq_purchase_date' => ['nullable', 'date'],
            'eq_purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'eq_next_maintenance' => ['nullable', 'date'],
        ]);

        $payload = [
            'branch_id' => $validated['eq_branch_id'],
            'name' => $validated['eq_name'],
            'category' => $validated['eq_category'] ?: null,
            'serial_number' => $validated['eq_serial'] ?: null,
            'condition' => $validated['eq_condition'],
            'purchase_date' => $validated['eq_purchase_date'] ?: null,
            'purchase_cost' => $validated['eq_purchase_cost'] !== '' ? $validated['eq_purchase_cost'] : null,
            'next_maintenance_at' => $validated['eq_next_maintenance'] ?: null,
        ];

        if ($this->editingEquipmentId) {
            Equipment::findOrFail($this->editingEquipmentId)->update($payload);
            session()->flash('success', 'Equipment updated.');
        } else {
            Equipment::create($payload);
            session()->flash('success', 'Equipment added.');
        }

        $this->showEquipmentModal = false;
    }

    public function openMaintenanceModal(int $equipmentId): void
    {
        abort_unless(Auth::user()->can('equipment.manage'), 403);

        $this->maint_equipment_id = $equipmentId;
        $this->maint_date = now()->toDateString();
        $this->maint_issue = '';
        $this->maint_action = '';
        $this->maint_cost = '0';
        $this->maint_performer = '';
        $this->maint_new_condition = 'good';
        $this->resetValidation();
        $this->showMaintenanceModal = true;
    }

    public function saveMaintenance(): void
    {
        abort_unless(Auth::user()->can('equipment.manage'), 403);

        $validated = $this->validate([
            'maint_equipment_id' => ['required', 'exists:equipment,id'],
            'maint_date' => ['required', 'date'],
            'maint_issue' => ['required', 'string', 'max:500'],
            'maint_action' => ['nullable', 'string', 'max:500'],
            'maint_cost' => ['required', 'numeric', 'min:0'],
            'maint_performer' => ['nullable', 'string', 'max:120'],
            'maint_new_condition' => ['required', 'in:good,needs_maintenance,out_of_order'],
        ]);

        DB::transaction(function () use ($validated) {
            MaintenanceLog::create([
                'equipment_id' => $validated['maint_equipment_id'],
                'performed_at' => $validated['maint_date'],
                'issue_description' => $validated['maint_issue'],
                'action_taken' => $validated['maint_action'] ?: null,
                'cost' => $validated['maint_cost'],
                'performed_by' => $validated['maint_performer'] ?: null,
                'logged_by' => Auth::id(),
            ]);

            Equipment::findOrFail($validated['maint_equipment_id'])->update([
                'condition' => $validated['maint_new_condition'],
                'last_maintained_at' => $validated['maint_date'],
            ]);
        });

        $this->showMaintenanceModal = false;
        session()->flash('success', 'Maintenance logged and equipment status updated.');
    }

    public function openProductModal(?int $productId = null): void
    {
        abort_unless(Auth::user()->can('inventory.manage'), 403);
        $this->resetValidation();
        $branchContext = app(BranchContext::class);

        if ($productId) {
            $p = Product::findOrFail($productId);
            $this->editingProductId = $p->id;
            $this->prod_branch_id = $p->branch_id;
            $this->prod_name = $p->name;
            $this->prod_sku = (string) $p->sku;
            $this->prod_category = (string) $p->category;
            $this->prod_price = (string) $p->price;
            $this->prod_cost = (string) ($p->cost ?? '0');
            $this->prod_stock = $p->stock_quantity;
            $this->prod_threshold = $p->low_stock_threshold;
        } else {
            $this->reset(['editingProductId', 'prod_name', 'prod_sku']);
            $this->prod_category = 'Supplements';
            $this->prod_price = '100.00';
            $this->prod_cost = '60.00';
            $this->prod_stock = 10;
            $this->prod_threshold = 5;
            $this->prod_branch_id = $branchContext->currentBranchId() ?? $branchContext->accessibleBranches()->first()?->id;
        }

        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        abort_unless(Auth::user()->can('inventory.manage'), 403);
        $allowedBranches = app(BranchContext::class)->accessibleBranches()->pluck('id')->all();

        $validated = $this->validate([
            'prod_branch_id' => ['required', Rule::in($allowedBranches)],
            'prod_name' => ['required', 'string', 'max:120'],
            'prod_sku' => ['nullable', 'string', 'max:40'],
            'prod_category' => ['nullable', 'string', 'max:60'],
            'prod_price' => ['required', 'numeric', 'min:0'],
            'prod_cost' => ['nullable', 'numeric', 'min:0'],
            'prod_stock' => ['required', 'integer', 'min:0'],
            'prod_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'branch_id' => $validated['prod_branch_id'],
            'name' => $validated['prod_name'],
            'sku' => $validated['prod_sku'] ?: null,
            'category' => $validated['prod_category'] ?: null,
            'price' => $validated['prod_price'],
            'cost' => $validated['prod_cost'] !== '' ? $validated['prod_cost'] : null,
            'stock_quantity' => $validated['prod_stock'],
            'low_stock_threshold' => $validated['prod_threshold'],
        ];

        if ($this->editingProductId) {
            Product::findOrFail($this->editingProductId)->update($payload);
            session()->flash('success', 'Product updated.');
        } else {
            Product::create($payload);
            session()->flash('success', 'Product added to inventory.');
        }

        $this->showProductModal = false;
    }

    public function restockProduct(int $productId, int $qty = 10): void
    {
        abort_unless(Auth::user()->can('inventory.manage'), 403);

        Product::findOrFail($productId)->increment('stock_quantity', $qty);
        session()->flash('success', "Restocked +{$qty} units.");
    }

    public function openSaleModal(int $productId): void
    {
        abort_unless(Auth::user()->can('inventory.manage'), 403);

        $this->sale_product_id = $productId;
        $this->sale_quantity = 1;
        $this->sale_method = 'cash';
        $this->resetValidation();
        $this->showSaleModal = true;
    }

    public function recordSale(): void
    {
        abort_unless(Auth::user()->can('inventory.manage'), 403);

        $validated = $this->validate([
            'sale_product_id' => ['required', 'exists:products,id'],
            'sale_quantity' => ['required', 'integer', 'min:1'],
            'sale_method' => ['required', 'in:cash,gcash,maya,card'],
        ]);

        $product = Product::findOrFail($validated['sale_product_id']);
        if ($product->stock_quantity < $validated['sale_quantity']) {
            $this->addError('sale_quantity', "Only {$product->stock_quantity} units left in stock.");

            return;
        }

        DB::transaction(function () use ($product, $validated) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $locked->decrement('stock_quantity', $validated['sale_quantity']);

            ProductSale::create([
                'branch_id' => $locked->branch_id,
                'product_id' => $locked->id,
                'quantity' => $validated['sale_quantity'],
                'unit_price' => $locked->price,
                'total_amount' => round((float) $locked->price * $validated['sale_quantity'], 2),
                'payment_method' => $validated['sale_method'],
                'sold_by' => Auth::id(),
                'sold_at' => now(),
            ]);
        });

        $this->showSaleModal = false;
        session()->flash('success', 'Product sale recorded and stock updated.');
    }

    public function render()
    {
        $branchContext = app(BranchContext::class);
        $scopedBranchIds = $branchContext->scopedBranchIds();

        $equipment = Equipment::with('branch')
            ->withCount('maintenanceLogs')
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->orderBy('name')
            ->get();

        $products = Product::with('branch')
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->orderBy('name')
            ->get();

        $sales = ProductSale::with(['product', 'branch', 'seller'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('branch_id', $scopedBranchIds))
            ->latest('sold_at')
            ->limit(30)
            ->get();

        return view('livewire.admin.inventory.inventory-manager', [
            'equipment' => $equipment,
            'products' => $products,
            'sales' => $sales,
            'branches' => $branchContext->accessibleBranches(),
            'canManageEquipment' => Auth::user()->can('equipment.manage'),
            'canManageInventory' => Auth::user()->can('inventory.manage'),
            'currencySymbol' => config('app.currency_symbol', '₱'),
        ]);
    }
}
