<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionAttachmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Settings\DocumentTypeController;
use App\Http\Controllers\Settings\FolderTreeController;
use App\Http\Controllers\Settings\LanguageController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\ReferenceNumberSettingsController;
use App\Http\Controllers\Settings\RoleManagementController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\TransactionStatusController;
use App\Http\Controllers\Settings\TransactionTypeController;
use App\Http\Controllers\Settings\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->middleware('permission:documents.download')
        ->name('documents.download');

    Route::middleware('permission:documents.view')->group(function () {
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    });

    Route::middleware('permission:documents.create')->group(function () {
        Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    });

    Route::middleware('permission:documents.view')->group(function () {
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    });

    Route::middleware('permission:documents.edit')->group(function () {
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::patch('documents/{document}', [DocumentController::class, 'update']);
    });

    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('permission:documents.delete')
        ->name('documents.destroy');

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

    Route::middleware('permission:transactions.create')->group(function () {
        Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    });

    Route::middleware('permission:transactions.view')->group(function () {
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('transactions/{transaction}/advance-status', [TransactionController::class, 'advanceStatus'])->name('transactions.advance-status');
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
            ->name('transactions.attachments.download');
        Route::get('transactions/{transaction}/attachments/{attachment}/preview', [TransactionAttachmentController::class, 'preview'])
            ->name('transactions.attachments.preview');
    });

    Route::middleware('permission:transactions.edit')->group(function () {
        Route::post('transactions/{transaction}/attachments/upload', [TransactionAttachmentController::class, 'storeUpload'])
            ->name('transactions.attachments.upload');
        Route::post('transactions/{transaction}/attachments/link', [TransactionAttachmentController::class, 'storeLink'])
            ->name('transactions.attachments.link');
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

        Route::middleware('permission:settings.users.edit')->group(function () {
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}', [UserManagementController::class, 'update']);
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

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('permission:profile.delete')
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';
