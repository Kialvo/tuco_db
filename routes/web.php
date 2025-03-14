<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WebsiteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// If you want root to go to login page:
Route::get('/', function () {
    return redirect('/login');
});
// Basic auth routes (provided by Breeze)
require __DIR__.'/auth.php';

// Dashboard route
Route::middleware('auth')->group(function() {
    Route::get('/dashboard', function() {
        return view('dashboard');
    })->name('dashboard');

    // Define the DataTables endpoint first:
    Route::match(['get','post'],'contacts/data', [ContactsController::class, 'getData'])->name('contacts.data');

    Route::post('/websites/{website}/restore', [WebsiteController::class, 'restore'])
        ->name('websites.restore');


    Route::post('/contacts/{contact}/restore', [ContactsController::class, 'restore'])
        ->name('contacts.restore');



    // Then define the resource routes for websites:
    Route::resource('contacts', ContactsController::class)->names([
        'index'   => 'contacts.index',
        'create'  => 'contacts.create',
        'store'   => 'contacts.store',
        'update'  => 'contacts.update',
        'destroy' => 'contacts.destroy',
    ]);

    Route::get('/contacts/{id}/edit-ajax', [ContactsController::class, 'editAjax'])
        ->name('contacts.editAjax');

    Route::get('/contacts/ajax/{id}', [ContactsController::class, 'showAjax'])
        ->name('contacts.showAjax');
    // Existing routes...
// Add two new GET routes for CSV and PDF export
    Route::get('/websites/export/csv', [WebsiteController::class, 'exportCsv'])
        ->name('websites.export.csv');
    Route::get('/websites/export/pdf', [WebsiteController::class, 'exportPdf'])
        ->name('websites.export.pdf');

    // Define the DataTables endpoint first:
    Route::match(['get','post'], 'websites/data', [WebsiteController::class, 'getData'])
        ->name('websites.data');
    // Then define the resource routes for websites:
    Route::resource('websites', WebsiteController::class)->names([
        'index'   => 'websites.index',
        'show'    => 'websites.show',
        'create'  => 'websites.create',
        'store'   => 'websites.store',
        'edit'    => 'websites.edit',
        'update'  => 'websites.update',
        'destroy' => 'websites.destroy',
    ]);

});

// Admin-Only: user management
Route::middleware(['auth', AdminMiddleware::class])->group(function () {
    Route::resource('admin/users', UserController::class)->names([
        'index'   => 'admin.users.index',
        'create'  => 'admin.users.create',
        'store'   => 'admin.users.store',
        'edit'    => 'admin.users.edit',
        'update'  => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    Route::get('/admin/users/{id}/edit-ajax', [UserController::class, 'editAjax'])->name('admin.users.editAjax');

});


// (If Breeze didn’t define it for you)
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
