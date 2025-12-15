<?php

use Illuminate\Support\Facades\Route;

// Redirect halaman awal ke login (opsional)
Route::get('/', function () {
    return redirect()->route('login');
});

// Group route khusus Admin yang sudah login
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});

require __DIR__.'/auth.php';