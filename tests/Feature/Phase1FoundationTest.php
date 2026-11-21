<?php

use App\Livewire\Admin\Branches\BranchIndex;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchContext;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('staff roles redirect from /dashboard to admin dashboard', function (string $role) {
    $branch = Branch::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->branches()->attach($branch->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard');
})->with(['super-admin', 'branch-manager', 'staff', 'trainer']);

test('members redirect from /dashboard to member portal and cannot access admin routes', function () {
    $member = User::factory()->create();
    $member->assignRole('member');

    $this->actingAs($member)
        ->get('/dashboard')
        ->assertRedirect(route('portal.home'));

    $this->actingAs($member)
        ->get(route('portal.home'))
        ->assertOk()
        ->assertSee('Member Portal');

    $this->actingAs($member)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.branches.index'))
        ->assertForbidden();
});

test('super admin can switch between all branches and a specific branch', function () {
    $branchA = Branch::factory()->create(['name' => 'Makati Main', 'code' => 'MKT']);
    $branchB = Branch::factory()->create(['name' => 'BGC Branch', 'code' => 'BGC']);

    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->post(route('admin.switch-branch'), ['branch_id' => $branchB->id])
        ->assertRedirect();

    expect(session(BranchContext::SESSION_KEY))->toBe($branchB->id);

    $this->actingAs($admin)
        ->post(route('admin.switch-branch'), ['branch_id' => 'all'])
        ->assertRedirect();

    expect(session(BranchContext::SESSION_KEY))->toBeNull();
});

test('branch manager cannot switch to an unassigned branch or view all branches', function () {
    $branchA = Branch::factory()->create(['code' => 'MKT']);
    $branchB = Branch::factory()->create(['code' => 'BGC']);

    $manager = User::factory()->create();
    $manager->assignRole('branch-manager');
    $manager->branches()->attach($branchA->id, ['is_primary' => true]);

    // Ensure middleware initializes to assigned primary branch
    $this->actingAs($manager)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(session(BranchContext::SESSION_KEY))->toBe($branchA->id);

    // Attempt switching to unassigned branch fails
    $this->actingAs($manager)
        ->post(route('admin.switch-branch'), ['branch_id' => $branchB->id])
        ->assertSessionHas('error');

    expect(session(BranchContext::SESSION_KEY))->toBe($branchA->id);

    // Attempt switching to all branches fails
    $this->actingAs($manager)
        ->post(route('admin.switch-branch'), ['branch_id' => 'all'])
        ->assertSessionHas('error');

    expect(session(BranchContext::SESSION_KEY))->toBe($branchA->id);
});

test('super admin can create, update, toggle, and archive branches via Livewire', function () {
    $main = Branch::factory()->create(['name' => 'Main Branch', 'code' => 'MAIN']);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin)
        ->test(BranchIndex::class)
        ->call('openCreateModal')
        ->set('name', 'BGC Taguig')
        ->set('code', 'bgc')
        ->set('address', 'High Street, BGC')
        ->set('phone', '+63 917 111 2222')
        ->set('email', 'bgc@ironpulse.gym')
        ->call('save')
        ->assertHasNoErrors();

    $bgc = Branch::where('code', 'BGC')->first();
    expect($bgc)->not->toBeNull()
        ->and($bgc->name)->toBe('BGC Taguig')
        ->and($bgc->allow_self_registration)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(BranchIndex::class)
        ->call('toggleSelfRegistration', $bgc->id)
        ->call('toggleActive', $bgc->id);

    $bgc->refresh();
    expect($bgc->allow_self_registration)->toBeFalse()
        ->and($bgc->is_active)->toBeFalse();

    Livewire::actingAs($admin)
        ->test(BranchIndex::class)
        ->call('deleteBranch', $bgc->id);

    expect(Branch::find($bgc->id))->toBeNull();
    expect(Branch::count())->toBe(1);
});

test('branch manager can view branches page but cannot create branches', function () {
    $branch = Branch::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('branch-manager');
    $manager->branches()->attach($branch->id, ['is_primary' => true]);

    $this->actingAs($manager)
        ->get(route('admin.branches.index'))
        ->assertOk()
        ->assertSee($branch->name);

    Livewire::actingAs($manager)
        ->test(BranchIndex::class)
        ->call('openCreateModal')
        ->assertForbidden();
});

test('new self-registered users automatically receive the member role', function () {
    $this->post('/register', [
        'name' => 'New Member',
        'email' => 'newmember@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'accept_waiver' => '1',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'newmember@example.com')->firstOrFail();
    expect($user->hasRole('member'))->toBeTrue();
});
