<?php

use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\MemberInviteController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Portal\HomeController as PortalHomeController;
use App\Http\Controllers\Portal\ProgramController as PortalProgramController;
use App\Http\Controllers\Portal\QrPassController;
use App\Http\Controllers\ProfileController;
use App\Models\Member;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Walk-in Member Password Setup Invite
Route::get('/invite/{token}', [MemberInviteController::class, 'show'])->name('invite.show');
Route::post('/invite/{token}', [MemberInviteController::class, 'accept'])->name('invite.accept');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// PDF Invoice Download (member owner or authorized staff)
Route::get('/invoices/{payment}/download', [InvoiceController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('invoices.download');

// Admin / Staff Panel
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:super-admin|branch-manager|staff|trainer', 'branch.context'])
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::post('/switch-branch', BranchSwitchController::class)->name('switch-branch');

        Route::view('/branches', 'admin.branches.index')
            ->middleware('permission:branches.view')
            ->name('branches.index');

        Route::view('/members', 'admin.members.index')
            ->middleware('permission:members.view')
            ->name('members.index');

        Route::get('/members/{member}', fn (Member $member) => view('admin.members.show', ['member' => $member]))
            ->middleware('permission:members.view')
            ->name('members.show');

        Route::view('/plans', 'admin.plans.index')
            ->middleware('permission:plans.view')
            ->name('plans.index');

        Route::view('/payments', 'admin.payments.index')
            ->middleware('permission:payments.view')
            ->name('payments.index');

        Route::view('/attendance', 'admin.attendance.index')
            ->middleware('permission:attendance.view')
            ->name('attendance.index');

        Route::view('/classes', 'admin.classes.index')
            ->middleware('permission:classes.view')
            ->name('classes.index');

        Route::view('/programs', 'admin.programs.index')
            ->middleware('permission:workouts.view|diets.view')
            ->name('programs.index');

        Route::view('/inventory', 'admin.inventory.index')
            ->middleware('permission:equipment.view|inventory.view')
            ->name('inventory.index');
    });

// Member Portal
Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', PortalHomeController::class)->name('home');
        Route::view('/membership', 'portal.membership')->name('membership');
        Route::get('/qr-pass', QrPassController::class)->name('qr-pass');
        Route::view('/classes', 'portal.classes')->name('classes');
        Route::get('/programs', PortalProgramController::class)->name('programs');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
