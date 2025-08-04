<?php

use App\Http\Controllers\HistoricalEntryController;
use App\Http\Controllers\NewEntryController;
use App\Http\Controllers\Tool\WebScraperController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;

/* ─────────────────────────────────────────────────────────────
 |  Controllers
 *───────────────────────────────────────────────────────────*/
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\CopyController;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\StorageController;

/*======================================================================
|  ROOT  →  login
=====================================================================*/
Route::get('/', fn () => redirect('/login'));

/*======================================================================
|  Breeze‑generated auth routes
=====================================================================*/
require __DIR__.'/auth.php';

/*======================================================================
|  AUTHENTICATED ROUTES
=====================================================================*/
Route::middleware('auth')->group(function () {

    /*--------------------------------------------------------------
    | Dashboard
    --------------------------------------------------------------*/
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    /*--------------------------------------------------------------
    | Contacts
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'contacts/data',   [ContactsController::class, 'getData'])->name('contacts.data');
    Route::post('/contacts/{contact}/restore',      [ContactsController::class, 'restore'])->name('contacts.restore');

    Route::get('/contacts/{id}/edit-ajax',          [ContactsController::class, 'editAjax'])->name('contacts.editAjax');
    Route::get('/contacts/ajax/{id}',               [ContactsController::class, 'showAjax'])->name('contacts.showAjax');

    Route::resource('contacts', ContactsController::class)->names([
        'index'   => 'contacts.index',
        'create'  => 'contacts.create',
        'store'   => 'contacts.store',
        'update'  => 'contacts.update',
        'destroy' => 'contacts.destroy',
    ]);

    /*--------------------------------------------------------------
    | Clients
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'clients/data',    [ClientsController::class, 'getData'])->name('clients.data');
    Route::post('/clients/{client}/restore',        [ClientsController::class, 'restore'])->name('clients.restore');

    Route::get('/clients/{id}/edit-ajax',           [ClientsController::class, 'editAjax'])->name('clients.editAjax');
    Route::get('/clients/ajax/{id}',                [ClientsController::class, 'showAjax'])->name('clients.showAjax');

    Route::resource('clients', ClientsController::class)->names([
        'index'   => 'clients.index',
        'create'  => 'clients.create',
        'store'   => 'clients.store',
        'update'  => 'clients.update',
        'destroy' => 'clients.destroy',
    ]);

    /*--------------------------------------------------------------
    | Copy
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'copy/data',       [CopyController::class,    'getData'])->name('copy.data');
    Route::post('/copy/{copy}/restore',             [CopyController::class,    'restore'])->name('copy.restore');

    Route::get('/copy/{id}/edit-ajax',              [CopyController::class,    'editAjax'])->name('copy.editAjax');
    Route::get('/copy/ajax/{id}',                   [CopyController::class,    'showAjax'])->name('copy.showAjax');

    Route::resource('copy', CopyController::class)->names([
        'index'   => 'copy.index',
        'create'  => 'copy.create',
        'store'   => 'copy.store',
        'update'  => 'copy.update',
        'destroy' => 'copy.destroy',
    ]);

    /*--------------------------------------------------------------
    | Websites
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'websites/data',   [WebsiteController::class, 'getData'])->name('websites.data');
    Route::post('/websites/{website}/restore',      [WebsiteController::class, 'restore'])->name('websites.restore');

    Route::get('/websites/export/csv',              [WebsiteController::class, 'exportCsv'])->name('websites.export.csv');
    Route::get('/websites/export/pdf',              [WebsiteController::class, 'exportPdf'])->name('websites.export.pdf');

    // routes/web.php
    Route::post('/websites/bulk-convert-eur',
        [WebsiteController::class, 'bulkConvertToEur']
    )->name('websites.bulkConvertToEur');


    Route::resource('websites', WebsiteController::class)->names([
        'index'   => 'websites.index',
        'show'    => 'websites.show',
        'create'  => 'websites.create',
        'store'   => 'websites.store',
        'edit'    => 'websites.edit',
        'update'  => 'websites.update',
        'destroy' => 'websites.destroy',
    ]);

    /*--------------------------------------------------------------
| New Entry
--------------------------------------------------------------*/

    /*  DataTables JSON feed (GET when you refresh page, POST via AJAX) */
    Route::match(
        ['get','post'],
        'new-entries/data',
        [NewEntryController::class,'getData']
    )->name('new_entries.data');

    /*  Inline status change (select dropdown in the table)  */
    Route::put(
        'new-entries/{new_entry}/status',
        [NewEntryController::class,'updateStatus']
    )->name('new_entries.status');

    /*  Soft-delete restore  */
    Route::post(
        'new-entries/{new_entry}/restore',
        [NewEntryController::class,'restore']
    )->name('new_entries.restore');

    /* ----------  “Historical” pre-filtered view  ---------- */
    /* UI page */
    Route::get('new-entries/historical',
        [HistoricalEntryController::class,'index']
    )->name('historical_view.index');

    /* DataTables JSON feed  (POST because we send many params) */
    Route::match(['get','post'],'new-entries/historical/data',
        [HistoricalEntryController::class,'getData']
    )->name('historical_view.data');

    /* ----------  FULL CRUD (resource) ----------
       parameters() keeps route-model binding variable singular (new_entry),
       names() gives you the explicit route names just like Websites. */
    Route::resource('new-entries', NewEntryController::class)
        ->parameters(['new-entries' => 'new_entry'])
        ->names([
            'index'   => 'new_entries.index',
            'show'    => 'new_entries.show',    // optional — remove if you don’t have a “show” blade
            'create'  => 'new_entries.create',
            'store'   => 'new_entries.store',
            'edit'    => 'new_entries.edit',
            'update'  => 'new_entries.update',
            'destroy' => 'new_entries.destroy',
        ]);


    /*--------------------------------------------------------------
    | Storages
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'storages/data',   [StorageController::class,'getData'])->name('storages.data');
    Route::post('/storages/{storage}/restore',      [StorageController::class,'restore'])->name('storages.restore');

    Route::get('/storages/export/csv',              [StorageController::class,'exportCsv'])->name('storages.export.csv');
    Route::get('/storages/export/pdf',              [StorageController::class,'exportPdf'])->name('storages.export.pdf');


    Route::post('/storages/bulk-update',   [StorageController::class,'bulkUpdate']
    )->name('storages.bulkUpdate');

    Route::post('/storages/undo',          [StorageController::class,'undo']
    )->name('storages.undo');

    Route::post('/storages/rollback',      [StorageController::class,'rollback']
    )->name('storages.rollback');



    /* ───── STORAGE summary row ───── */
    Route::post('/storages/summary', [StorageController::class,'summary'])
        ->name('storages.summary');


    Route::resource('storages', StorageController::class)->names([
        'index'   => 'storages.index',
        'show'    => 'storages.show',
        'create'  => 'storages.create',
        'store'   => 'storages.store',
        'edit'    => 'storages.edit',
        'update'  => 'storages.update',
        'destroy' => 'storages.destroy',
    ]);


    /*--------------------------------------------------------------
  | SCRAPER
  --------------------------------------------------------------*/


    Route::middleware('auth')->prefix('tools')->name('tools.')->group(function () {
        Route::view ('discover',           'tools.discover')->name('discover');        // page
        Route::post ('discover/search',    [WebScraperController::class, 'search'])->name('discover.search');
        Route::post ('discover/import',    [WebScraperController::class, 'import'])->name('discover.import');
        Route::get  ('discover/export',    [WebScraperController::class, 'exportCsv'])->name('discover.export');
    });

});

/*======================================================================
|  ADMIN‑ONLY  (User management)
=====================================================================*/
Route::middleware(['auth', AdminMiddleware::class])->group(function () {

    Route::get('/admin/users/{id}/edit-ajax', [UserController::class, 'editAjax'])
        ->name('admin.users.editAjax');

    Route::resource('admin/users', UserController::class)->names([
        'index'   => 'admin.users.index',
        'create'  => 'admin.users.create',
        'store'   => 'admin.users.store',
        'edit'    => 'admin.users.edit',
        'update'  => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
});

/*======================================================================
|  LOGOUT  (for Breeze, if not auto‑generated)
=====================================================================*/
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
