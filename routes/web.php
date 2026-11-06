<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'can:dashboard.view'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [PasswordController::class, 'update'])->name('profile.password.update');

    // Super Admin: User Management (PRD §5.2)
    Route::resource('users', UserController::class)
        ->except(['destroy'])
        ->middleware('can:users.manage');

    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])
        ->middleware('can:users.manage')
        ->name('users.status');

    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('can:users.manage')
        ->name('users.reset-password');

    // Super Admin: Role & Permission Matrix (FR-USR-06)
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('can:roles.view')
        ->name('roles.index');
});

require __DIR__.'/auth.php';
