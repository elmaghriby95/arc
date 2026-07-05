<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\ScanAgentProxyController;
use App\Http\Controllers\TransactionAttachmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionReviewLogController;
use App\Http\Controllers\Settings\DocumentTypeController;
use App\Http\Controllers\Settings\FolderTreeController;
use App\Http\Controllers\Settings\LanguageController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\ReferenceNumberSettingsController;
use App\Http\Controllers\Settings\RoleManagementController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\TransactionStatusController;
use App\Http\Controllers\Settings\TransactionTypeController;
use App\Http\Controllers\Settings\TranslationController;
use App\Http\Controllers\Settings\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::post('locale/{language}', [\App\Http\Controllers\LocaleController::class, 'switch'])
    ->name('locale.switch');

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->homeUrl())
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{type}', [ReportController::class, 'show'])->name('show');
        Route::get('/{type}/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{type}/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    });

    Route::get('documents/{attachment}/download', [DocumentController::class, 'download'])
        ->middleware('permission:documents.download')
        ->name('documents.download');

    Route::middleware('permission:documents.view')->group(function () {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{attachment}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
        Route::get('documents/{attachment}', [DocumentController::class, 'show'])->name('documents.show');
    });

    Route::middleware('permission:departments.view')->group(function () {
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
    });

    Route::middleware('permission:departments.create')->group(function () {
        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    });

    Route::middleware('permission:departments.edit')->group(function () {
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('departments/{department}', [DepartmentController::class, 'update']);
    });

    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:departments.delete')
        ->name('departments.destroy');

    Route::middleware('permission:categories.view')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    });

    Route::middleware('permission:categories.create')->group(function () {
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    });

    Route::middleware('permission:categories.edit')->group(function () {
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('categories/{category}', [CategoryController::class, 'update']);
    });

    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:categories.delete')
        ->name('categories.destroy');

    Route::middleware('permission:transactions.view')->group(function () {
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    });

    Route::middleware('permission:transactions.review-log.view')->group(function () {
        Route::get('transactions/review-log', [TransactionReviewLogController::class, 'index'])->name('transactions.review-log');
    });

    Route::middleware('permission:transactions.create')->group(function () {
        Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');

        Route::prefix('scan-agent')->name('scan-agent.')->group(function () {
            Route::get('health', [ScanAgentProxyController::class, 'health'])->name('health');
            Route::get('devices', [ScanAgentProxyController::class, 'devices'])->name('devices');
            Route::post('scan', [ScanAgentProxyController::class, 'scan'])->name('scan');
        });
    });

    Route::middleware('permission:transactions.view')->group(function () {
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('transactions/{transaction}/transition', [TransactionController::class, 'transition'])->name('transactions.transition');
    });

    Route::middleware('permission:transactions.edit')->group(function () {
        Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::patch('transactions/{transaction}', [TransactionController::class, 'update']);
    });

    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])
        ->middleware('permission:transactions.delete')
        ->name('transactions.destroy');

    Route::middleware('permission:transactions.view')->group(function () {
        Route::get('transactions/{transaction}/attachments/{attachment}/download', [TransactionAttachmentController::class, 'download'])
            ->middleware('permission:documents.download')
            ->name('transactions.attachments.download');
        Route::get('transactions/{transaction}/attachments/{attachment}/preview', [TransactionAttachmentController::class, 'preview'])
            ->middleware('permission:documents.view')
            ->name('transactions.attachments.preview');
    });

    Route::middleware('permission:transactions.edit')->group(function () {
        Route::post('transactions/{transaction}/attachments/upload', [TransactionAttachmentController::class, 'storeUpload'])
            ->name('transactions.attachments.upload');
        Route::delete('transactions/{transaction}/attachments/{attachment}', [TransactionAttachmentController::class, 'destroy'])
            ->name('transactions.attachments.destroy');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('index');

        Route::get('/users', [UserManagementController::class, 'index'])
            ->middleware('permission:settings.users.view')
            ->name('users.index');

        Route::middleware('permission:settings.users.create')->group(function () {
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        });

        Route::middleware('permission:settings.users.edit')->group(function () {
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}', [UserManagementController::class, 'update']);
            Route::post('/users/{user}/avatar', [UserManagementController::class, 'updateAvatar'])->name('users.avatar.update');
            Route::delete('/users/{user}/avatar', [UserManagementController::class, 'destroyAvatar'])->name('users.avatar.destroy');
        });

        Route::get('/roles', [RoleManagementController::class, 'index'])
            ->middleware('permission:settings.roles.view')
            ->name('roles.index');

        Route::middleware('permission:settings.roles.create')->group(function () {
            Route::get('/roles/create', [RoleManagementController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleManagementController::class, 'store'])->name('roles.store');
        });

        Route::middleware('permission:settings.roles.edit')->group(function () {
            Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
            Route::patch('/roles/{role}', [RoleManagementController::class, 'update']);
        });

        Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])
            ->middleware('permission:settings.roles.delete')
            ->name('roles.destroy');

        Route::get('/organization', [OrganizationController::class, 'index'])
            ->middleware('permission:settings.organization.view')
            ->name('organization.index');

        Route::middleware('permission:departments.create')->group(function () {
            Route::post('/organization', [OrganizationController::class, 'store'])->name('organization.store');
        });

        Route::middleware('permission:departments.edit')->group(function () {
            Route::put('/organization/{department}', [OrganizationController::class, 'update'])->name('organization.update');
            Route::patch('/organization/{department}', [OrganizationController::class, 'update']);
        });

        Route::delete('/organization/{department}', [OrganizationController::class, 'destroy'])
            ->middleware('permission:departments.delete')
            ->name('organization.destroy');

        Route::middleware('permission:settings.document-types.view')->group(function () {
            Route::get('/document-types', [DocumentTypeController::class, 'index'])->name('document-types.index');
        });

        Route::middleware('permission:settings.document-types.create')->group(function () {
            Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
        });

        Route::middleware('permission:settings.document-types.edit')->group(function () {
            Route::put('/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
            Route::patch('/document-types/{documentType}', [DocumentTypeController::class, 'update']);
        });

        Route::delete('/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])
            ->middleware('permission:settings.document-types.delete')
            ->name('document-types.destroy');

        Route::middleware('permission:settings.transaction-types.view')->group(function () {
            Route::get('/transaction-types', [TransactionTypeController::class, 'index'])->name('transaction-types.index');
        });

        Route::middleware('permission:settings.transaction-types.create')->group(function () {
            Route::post('/transaction-types', [TransactionTypeController::class, 'store'])->name('transaction-types.store');
        });

        Route::middleware('permission:settings.transaction-types.edit')->group(function () {
            Route::put('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
            Route::patch('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update']);
        });

        Route::delete('/transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])
            ->middleware('permission:settings.transaction-types.delete')
            ->name('transaction-types.destroy');

        Route::middleware('permission:settings.transaction-statuses.view')->group(function () {
            Route::get('/transaction-statuses', [TransactionStatusController::class, 'index'])->name('transaction-statuses.index');
        });

        Route::middleware('permission:settings.transaction-statuses.create')->group(function () {
            Route::post('/transaction-statuses', [TransactionStatusController::class, 'store'])->name('transaction-statuses.store');
        });

        Route::middleware('permission:settings.transaction-statuses.edit')->group(function () {
            Route::put('/transaction-statuses/{transactionStatus}', [TransactionStatusController::class, 'update'])->name('transaction-statuses.update');
            Route::patch('/transaction-statuses/{transactionStatus}', [TransactionStatusController::class, 'update']);
        });

        Route::delete('/transaction-statuses/{transactionStatus}', [TransactionStatusController::class, 'destroy'])
            ->middleware('permission:settings.transaction-statuses.delete')
            ->name('transaction-statuses.destroy');

        Route::middleware('permission:settings.folders.view')->group(function () {
            Route::get('/folders', [FolderTreeController::class, 'index'])->name('folders.index');
        });

        Route::middleware('permission:settings.folders.create')->group(function () {
            Route::post('/folders', [FolderTreeController::class, 'store'])->name('folders.store');
        });

        Route::middleware('permission:settings.folders.edit')->group(function () {
            Route::put('/folders/{folder}', [FolderTreeController::class, 'update'])->name('folders.update');
            Route::patch('/folders/{folder}', [FolderTreeController::class, 'update']);
        });

        Route::delete('/folders/{folder}', [FolderTreeController::class, 'destroy'])
            ->middleware('permission:settings.folders.delete')
            ->name('folders.destroy');

        Route::middleware('permission:settings.languages.view')->group(function () {
            Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
        });

        Route::middleware('permission:settings.languages.create')->group(function () {
            Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
        });

        Route::middleware('permission:settings.languages.edit')->group(function () {
            Route::put('/languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
            Route::patch('/languages/{language}', [LanguageController::class, 'update']);
        });

        Route::delete('/languages/{language}', [LanguageController::class, 'destroy'])
            ->middleware('permission:settings.languages.delete')
            ->name('languages.destroy');

        Route::middleware('permission:settings.languages.view')->group(function () {
            Route::get('/languages/{language}/translations', [TranslationController::class, 'index'])
                ->name('languages.translations');
        });

        Route::middleware('permission:settings.languages.edit')->group(function () {
            Route::post('/languages/{language}/translations', [TranslationController::class, 'update'])
                ->name('languages.translations.update');
            Route::post('/languages/{language}/translations/keys', [TranslationController::class, 'storeKey'])
                ->name('languages.translations.keys.store');
            Route::delete('/languages/{language}/translations/keys/{translationKey}', [TranslationController::class, 'destroyKey'])
                ->name('languages.translations.keys.destroy');
        });

        Route::middleware('permission:settings.reference-numbers.view')->group(function () {
            Route::get('/reference-numbers', [ReferenceNumberSettingsController::class, 'index'])->name('reference-numbers.index');
        });

        Route::middleware('permission:settings.reference-numbers.edit')->group(function () {
            Route::put('/reference-numbers', [ReferenceNumberSettingsController::class, 'update'])->name('reference-numbers.update');
            Route::patch('/reference-numbers', [ReferenceNumberSettingsController::class, 'update']);
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:profile.view')
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:profile.edit')
        ->name('profile.update');

    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->middleware('permission:profile.edit')
        ->name('profile.avatar.update');

    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])
        ->middleware('permission:profile.edit')
        ->name('profile.avatar.destroy');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('permission:profile.delete')
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
