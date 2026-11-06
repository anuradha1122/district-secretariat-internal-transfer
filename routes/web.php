<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfficeController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/offices', [OfficeController::class, 'index'])
        ->name('offices.index');

    Route::post('/offices', [OfficeController::class, 'store'])
        ->name('offices.store');

    Route::put('/offices/{office}', [OfficeController::class, 'update'])
        ->name('offices.update');
});

require __DIR__.'/settings.php';
