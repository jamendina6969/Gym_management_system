<?php

use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Portal\HomeController as PortalHomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
    });

// Member Portal
Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/', PortalHomeController::class)->name('home');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
