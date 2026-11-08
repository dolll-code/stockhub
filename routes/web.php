<?php

use App\Http\Controllers\AjaxProductController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
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

    // Master Data: Kategori (PRD §5.4)
    Route::resource('categories', CategoryController::class)
        ->except(['show', 'destroy']);
    Route::patch('/categories/{category}/status', [CategoryController::class, 'toggleStatus'])
        ->name('categories.status');

    // Master Data: Satuan (PRD §5.5)
    Route::resource('units', UnitController::class)
        ->except(['show', 'destroy']);
    Route::patch('/units/{unit}/status', [UnitController::class, 'toggleStatus'])
        ->name('units.status');

    // Master Data: Supplier (PRD §5.6)
    Route::resource('suppliers', SupplierController::class)
        ->except(['destroy']);
    Route::patch('/suppliers/{supplier}/status', [SupplierController::class, 'toggleStatus'])
        ->name('suppliers.status');

    // Master Data: Barang (PRD §5.7)
    Route::resource('products', ProductController::class)
        ->except(['destroy']);
    Route::patch('/products/{product}/status', [ProductController::class, 'toggleStatus'])
        ->name('products.status');
    Route::delete('/products/{product}/photo', [ProductController::class, 'deletePhoto'])
        ->name('products.photo.destroy');

    // Transaksi: Barang Masuk (PRD §5.8)
    Route::resource('stock-ins', StockInController::class)
        ->except(['destroy']);
    Route::post('/stock-ins/{stock_in}/confirm', [StockInController::class, 'confirm'])
        ->name('stock-ins.confirm');
    Route::post('/stock-ins/{stock_in}/cancel', [StockInController::class, 'cancel'])
        ->name('stock-ins.cancel');

    // Transaksi: Barang Keluar (PRD §5.9)
    Route::resource('stock-outs', StockOutController::class)
        ->except(['destroy']);
    Route::post('/stock-outs/{stock_out}/confirm', [StockOutController::class, 'confirm'])
        ->name('stock-outs.confirm');
    Route::post('/stock-outs/{stock_out}/cancel', [StockOutController::class, 'cancel'])
        ->name('stock-outs.cancel');

    // Transaksi: Penyesuaian Stok Opname (PRD §5.10)
    Route::resource('adjustments', StockAdjustmentController::class)
        ->except(['edit', 'update', 'destroy']);

    // Endpoint AJAX JSON (PRD §12.3)
    Route::get('/ajax/products/search', [AjaxProductController::class, 'search'])
        ->middleware('can:products.view')
        ->name('ajax.products.search');
    Route::get('/ajax/products/{product}/stock', [AjaxProductController::class, 'stock'])
        ->middleware('can:products.view')
        ->name('ajax.products.stock');
});

require __DIR__.'/auth.php';
