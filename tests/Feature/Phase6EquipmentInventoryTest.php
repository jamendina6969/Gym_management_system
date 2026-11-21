<?php

use App\Livewire\Admin\Inventory\InventoryManager;
use App\Models\Branch;
use App\Models\Equipment;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('branch manager can track equipment, log maintenance, manage products, and record stock-deducting sales', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('branch-manager');
    $manager->branches()->attach($branch->id, ['is_primary' => true]);

    // Add equipment
    Livewire::actingAs($manager)
        ->test(InventoryManager::class)
        ->call('openEquipmentModal')
        ->set('eq_branch_id', $branch->id)
        ->set('eq_name', 'Commercial Treadmill #1')
        ->set('eq_category', 'Cardio')
        ->set('eq_condition', 'needs_maintenance')
        ->call('saveEquipment')
        ->assertHasNoErrors();

    $eq = Equipment::firstOrFail();
    expect($eq->condition)->toBe('needs_maintenance');

    // Log maintenance & restore to good condition
    Livewire::actingAs($manager)
        ->test(InventoryManager::class)
        ->call('openMaintenanceModal', $eq->id)
        ->set('maint_date', now()->toDateString())
        ->set('maint_issue', 'Belt slipping')
        ->set('maint_action', 'Replaced drive belt and lubricated deck')
        ->set('maint_cost', '1200')
        ->set('maint_new_condition', 'good')
        ->call('saveMaintenance')
        ->assertHasNoErrors();

    expect($eq->fresh()->condition)->toBe('good')
        ->and($eq->maintenanceLogs()->count())->toBe(1);

    // Add product & record sale
    Livewire::actingAs($manager)
        ->test(InventoryManager::class)
        ->call('openProductModal')
        ->set('prod_branch_id', $branch->id)
        ->set('prod_name', 'Whey Protein Shake')
        ->set('prod_price', '150.00')
        ->set('prod_stock', 5)
        ->set('prod_threshold', 2)
        ->call('saveProduct')
        ->assertHasNoErrors();

    $product = Product::firstOrFail();

    // Prevent overselling
    Livewire::actingAs($manager)
        ->test(InventoryManager::class)
        ->call('openSaleModal', $product->id)
        ->set('sale_quantity', 10)
        ->call('recordSale')
        ->assertHasErrors('sale_quantity');

    // Valid sale deducts stock
    Livewire::actingAs($manager)
        ->test(InventoryManager::class)
        ->call('openSaleModal', $product->id)
        ->set('sale_quantity', 3)
        ->set('sale_method', 'gcash')
        ->call('recordSale')
        ->assertHasNoErrors();

    expect($product->fresh()->stock_quantity)->toBe(2)
        ->and($product->fresh()->isLowStock())->toBeTrue()
        ->and(ProductSale::count())->toBe(1)
        ->and((float) ProductSale::first()->total_amount)->toBe(450.0);
});
