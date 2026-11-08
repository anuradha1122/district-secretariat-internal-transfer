<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EligibleEmployeeController;
use App\Http\Controllers\Admin\EmployeeActivationCodeController;
use App\Http\Controllers\EmployeeActivationController;
use App\Http\Middleware\EnsureEmployeeIsActiveAndEmailIsVerified;
use App\Http\Controllers\Admin\EmployeePasswordResetCodeController;
use App\Http\Controllers\EmployeePasswordResetController;
use App\Http\Controllers\EmployeeProfileController;
use App\Http\Controllers\TransferCycleController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware([
    'auth',
    EnsureEmployeeIsActiveAndEmailIsVerified::class,
])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware([
    'auth',
    EnsureEmployeeIsActiveAndEmailIsVerified::class,
])->group(function () {
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

    Route::get(
        '/activation-codes',
        [EmployeeActivationCodeController::class, 'index']
    )->name('activation-codes.index');

    Route::post(
        '/activation-codes/{eligibleEmployee}',
        [EmployeeActivationCodeController::class, 'issue']
    )->name('activation-codes.issue');

    Route::delete(
        '/activation-codes/{eligibleEmployee}',
        [EmployeeActivationCodeController::class, 'revoke']
    )->name('activation-codes.revoke');

    Route::get(
        '/employee-reset-codes',
        [EmployeePasswordResetCodeController::class, 'index']
    )->name('employee-reset-codes.index');

    Route::post(
        '/employee-reset-codes',
        [EmployeePasswordResetCodeController::class, 'issue']
    )->name('employee-reset-codes.issue');

    Route::delete(
        '/employee-reset-codes',
        [EmployeePasswordResetCodeController::class, 'revoke']
    )->name('employee-reset-codes.revoke');

    Route::get(
        '/employee-profile',
        [EmployeeProfileController::class, 'edit']
    )->name('employee-profile.edit');

    Route::put(
        '/employee-profile',
        [EmployeeProfileController::class, 'update']
    )->name('employee-profile.update');

    Route::get(
        '/transfer-cycles',
        [TransferCycleController::class, 'index']
    )->name('transfer-cycles.index');

    Route::post(
        '/transfer-cycles',
        [TransferCycleController::class, 'store']
    )->name('transfer-cycles.store');

    Route::put(
        '/transfer-cycles/{transferCycle}',
        [TransferCycleController::class, 'update']
    )->name('transfer-cycles.update');
});

Route::middleware('guest')->group(function () {
    Route::get(
        '/activate-employee',
        [EmployeeActivationController::class, 'create']
    )->name('employee-activation.create');

    Route::post(
        '/activate-employee',
        [EmployeeActivationController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('employee-activation.store');

    Route::get(
        '/employee-password-reset',
        [EmployeePasswordResetController::class, 'create']
    )->name('employee-password-reset.create');

    Route::post(
        '/employee-password-reset',
        [EmployeePasswordResetController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('employee-password-reset.store');
});

require __DIR__.'/settings.php';
