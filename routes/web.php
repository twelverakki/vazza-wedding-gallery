<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Clear Cache Route (untuk production)
Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');

    return '<h1 style="color: green; font-family: sans-serif; text-align: center; margin-top: 50px;">✅ Cache Cleared Successfully!</h1>
            <p style="text-align: center; font-family: sans-serif;">
                <a href="/" style="color: #ec4899; text-decoration: none; font-weight: bold;">← Kembali ke Dashboard</a>
            </p>';
});

// Run Migration Route (Khusus Admin Deployment)
Route::get('/run-migration', function () {
    Artisan::call('migrate', ['--force' => true]);
    return '<h1 style="color: green; font-family: sans-serif; text-align: center; margin-top: 50px;">✅ Database Migrated Successfully!</h1>
            <p style="text-align: center; font-family: sans-serif;">
                <a href="/" style="color: #ec4899; text-decoration: none; font-weight: bold;">← Kembali ke Dashboard</a>
            </p>';
})->middleware(['auth', 'verified']); // Protect with auth

// Redirect halaman awal ke login (opsional)
Route::get('/', function () {
    return redirect()->route('login');
});

// Group route khusus Admin yang sudah login
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/cancelled', [\App\Http\Controllers\ReportController::class, 'cancelled'])->name('reports.cancelled');


    Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [ProductController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{product}/edit', [ProductController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{product}', [ProductController::class, 'destroy'])->name('inventory.destroy');

    // Rental Routes
    Route::get('rentals/parse', [\App\Http\Controllers\RentalController::class, 'parseInput'])->name('rentals.parse');
    Route::post('rentals/parse', [\App\Http\Controllers\RentalController::class, 'processParse'])->name('rentals.processParse');
    Route::get('rentals/fix-duplicates', [\App\Http\Controllers\RentalController::class, 'fixDuplicates'])->name('rentals.fixDuplicates');
    Route::resource('rentals', \App\Http\Controllers\RentalController::class);
    Route::post('rentals/{item}/update-mark', [\App\Http\Controllers\RentalController::class, 'updateItemMark'])->name('rentals.updateItemMark');
    Route::post('rentals/{rental}/bulk-update-items', [\App\Http\Controllers\RentalController::class, 'bulkUpdateItemMark'])->name('rentals.bulkUpdateItemMark');
    Route::put('rentals/{rental}/update-notes', [\App\Http\Controllers\RentalController::class, 'updateNotes'])->name('rentals.updateNotes');
    Route::post('rentals/{rental}/additional-cost', [\App\Http\Controllers\RentalController::class, 'updateAdditionalCost'])->name('rentals.updateAdditionalCost');
    Route::post('rentals/{rental}/fine', [\App\Http\Controllers\RentalController::class, 'storeFine'])->name('rentals.storeFine');
    Route::delete('fines/{fine}', [\App\Http\Controllers\RentalController::class, 'destroyFine'])->name('fines.destroy');
    Route::post('rentals/{rental}/pay', [\App\Http\Controllers\RentalController::class, 'markAsPaid'])->name('rentals.pay');
    Route::post('rentals/{rental}/cancel', [\App\Http\Controllers\RentalController::class, 'cancel'])->name('rentals.cancel');
    Route::post('rentals/{rental}/update-rental-status', [\App\Http\Controllers\RentalController::class, 'updateRentalStatus'])->name('rentals.updateStatus');
    Route::post('rentals/{rental}/generate-invoice', [\App\Http\Controllers\RentalController::class, 'generateInvoice'])->name('rentals.generateInvoice');
    Route::get('rentals/{rental}/invoice', [\App\Http\Controllers\RentalController::class, 'downloadInvoice'])->name('rentals.invoice');
});


require __DIR__ . '/auth.php';