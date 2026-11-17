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
use App\Http\Controllers\TransferApplicationController;
use App\Http\Controllers\TransferApplicationSubmissionController;
use App\Http\Controllers\Admin\OfficeReviewerAssignmentController;
use App\Http\Controllers\OfficeReviewController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\OfficeReviewDecisionController;
use App\Http\Controllers\DistrictReviewController;
use App\Http\Controllers\DistrictReviewDecisionController;
use App\Http\Controllers\MutualTransferPreviewController;
use App\Http\Controllers\MutualMatchRunController;
use App\Http\Controllers\MutualMatchDecisionController;
use App\Http\Controllers\ConfirmedMatchReconciliationController;
use App\Http\Controllers\ManagementReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware([
    'auth',
    EnsureEmployeeIsActiveAndEmailIsVerified::class,
])->group(function () {
    Route::get('dashboard', DashboardController::class)
    ->name('dashboard');
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

    Route::get(
        '/transfer-applications',
        [TransferApplicationController::class, 'index']
    )->name('transfer-applications.index');

    Route::post(
        '/transfer-applications',
        [TransferApplicationController::class, 'store']
    )->name('transfer-applications.store');

    Route::get(
        '/transfer-applications/{transferApplication}/edit',
        [TransferApplicationController::class, 'edit']
    )->name('transfer-applications.edit');

    Route::put(
        '/transfer-applications/{transferApplication}',
        [TransferApplicationController::class, 'update']
    )->name('transfer-applications.update');

    Route::get(
        '/transfer-applications/{transferApplication}/review',
        [TransferApplicationSubmissionController::class, 'review']
    )->name('transfer-applications.review');

    Route::post(
        '/transfer-applications/{transferApplication}/submit',
        [TransferApplicationSubmissionController::class, 'submit']
    )->name('transfer-applications.submit');

    Route::get(
        '/transfer-applications/{transferApplication}/pdf',
        [TransferApplicationSubmissionController::class, 'pdf']
    )->name('transfer-applications.pdf');

    Route::get(
        '/office-reviewer-assignments',
        [OfficeReviewerAssignmentController::class, 'index']
    )->name('office-reviewer-assignments.index');

    Route::post(
        '/office-reviewer-assignments',
        [OfficeReviewerAssignmentController::class, 'store']
    )->name('office-reviewer-assignments.store');

    Route::delete(
        '/office-reviewer-assignments/{officeReviewerAssignment}',
        [OfficeReviewerAssignmentController::class, 'destroy']
    )->name('office-reviewer-assignments.destroy');

    Route::get(
        '/office-reviews',
        [OfficeReviewController::class, 'index']
    )->name('office-reviews.index');

    Route::get(
        '/office-reviews/{transferApplication}',
        [OfficeReviewController::class, 'show']
    )->name('office-reviews.show');

    Route::get(
        '/user-management',
        [UserManagementController::class, 'index']
    )->name('user-management.index');

    Route::post(
        '/user-management',
        [UserManagementController::class, 'store']
    )->name('user-management.store');

    Route::put(
        '/user-management/{managedUser}/roles',
        [UserManagementController::class, 'updateRoles']
    )->name('user-management.roles.update');

    Route::post(
        '/office-reviews/{transferApplication}/decision',
        [OfficeReviewDecisionController::class, 'store']
    )->name('office-reviews.decision.store');


    Route::get(
        '/district-reviews',
        [DistrictReviewController::class, 'index']
    )->name('district-reviews.index');

    Route::get(
        '/district-reviews/{transferApplication}',
        [DistrictReviewDecisionController::class, 'show']
    )->name('district-reviews.show');

    Route::post(
        '/district-reviews/{transferApplication}/decision',
        [DistrictReviewDecisionController::class, 'store']
    )->name('district-reviews.decision.store');

    Route::get(
        '/mutual-transfers',
        [MutualTransferPreviewController::class, 'index']
    )->name('mutual-transfers.index');

    Route::get(
        '/mutual-match-runs',
        [MutualMatchRunController::class, 'index']
    )->name('mutual-match-runs.index');

    Route::post(
        '/mutual-match-runs',
        [MutualMatchRunController::class, 'store']
    )->name('mutual-match-runs.store');

    Route::get(
        '/mutual-match-runs/{mutualMatchRun}',
        [MutualMatchRunController::class, 'show']
    )->name('mutual-match-runs.show');

    Route::post(
        '/mutual-match-runs/{mutualMatchRun}/decisions',
        [MutualMatchDecisionController::class, 'store']
    )->name('mutual-match-decisions.store');

    Route::get(
        '/mutual-match-reconciliation',
        [ConfirmedMatchReconciliationController::class, 'index']
    )->name('mutual-match-reconciliation.index');

    Route::get(
        '/reports',
        [ManagementReportController::class, 'index']
    )->name('reports.index');

    Route::get(
        '/reports/export/csv',
        [ManagementReportController::class, 'csv']
    )->name('reports.csv');

    Route::get(
        '/reports/export/pdf',
        [ManagementReportController::class, 'pdf']
    )->name('reports.pdf');

    Route::middleware([
        'auth',
        \App\Http\Middleware\EnsureEmployeeIsActiveAndEmailIsVerified::class,
    ])->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        Route::post('/notifications/read-all', [
            NotificationController::class,
            'markAllAsRead',
        ])->name('notifications.read-all');

        Route::post('/notifications/{notificationId}/read', [
            NotificationController::class,
            'markAsRead',
        ])->name('notifications.read');
    });
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
