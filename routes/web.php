<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EligibleEmployeeController;

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

    Route::get('/services', [ServiceController::class, 'index'])
        ->name('services.index');

    Route::post('/services', [ServiceController::class, 'store'])
        ->name('services.store');

    Route::put('/services/{service}', [ServiceController::class, 'update'])
        ->name('services.update');

    Route::get(
    '/eligible-employees',
        [EligibleEmployeeController::class, 'index']
    )->name('eligible-employees.index');

    Route::get(
        '/eligible-employees/template',
        [EligibleEmployeeController::class, 'template']
    )->name('eligible-employees.template');

    Route::post(
        '/eligible-employees/import',
        [EligibleEmployeeController::class, 'import']
    )->name('eligible-employees.import');

    Route::patch(
        '/eligible-employees/{eligibleEmployee}/status',
        [EligibleEmployeeController::class, 'updateStatus']
    )->name('eligible-employees.status');
});

require __DIR__.'/settings.php';
