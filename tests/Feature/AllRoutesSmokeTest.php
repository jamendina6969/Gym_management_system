<?php

use App\Models\Branch;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('all admin and member portal routes render 200 OK cleanly with seeded demo data', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@ironpulse.gym')->firstOrFail();
    $memberUser = User::where('email', 'member@ironpulse.gym')->firstOrFail();
    $memberProfile = Member::where('user_id', $memberUser->id)->firstOrFail();

    // Public routes
    $this->get('/')->assertOk()->assertSee('IronPulse Gym');
    $this->get('/login')->assertOk();
    $this->get('/register')->assertOk();

    // Admin routes
    foreach ([
        route('admin.dashboard'),
        route('admin.branches.index'),
        route('admin.members.index'),
        route('admin.members.show', $memberProfile),
        route('admin.plans.index'),
        route('admin.payments.index'),
        route('admin.attendance.index'),
        route('admin.classes.index'),
        route('admin.programs.index'),
        route('admin.inventory.index'),
        route('admin.reports.index'),
        route('admin.settings.index'),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }

    // Member Portal routes
    foreach ([
        route('portal.home'),
        route('portal.membership'),
        route('portal.qr-pass'),
        route('portal.classes'),
        route('portal.programs'),
    ] as $url) {
        $this->actingAs($memberUser)->get($url)->assertOk();
    }
});
