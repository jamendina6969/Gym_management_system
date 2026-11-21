<?php

use App\Models\Branch;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin dashboard renders analytics charts and reports export CSV and PDF', function () {
    $branch = Branch::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $member = Member::create([
        'user_id' => User::factory()->create()->id,
        'home_branch_id' => $branch->id,
        'status' => 'active',
    ]);

    Payment::create([
        'branch_id' => $branch->id,
        'member_id' => $member->id,
        'amount' => 2500.00,
        'payment_method' => 'gcash',
        'gateway' => 'manual',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Revenue Trend')
        ->assertSee('2,500.00');

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertSee('2,500.00');

    $this->actingAs($admin)
        ->get(route('admin.reports.export-csv'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs($admin)
        ->get(route('admin.reports.export-pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
