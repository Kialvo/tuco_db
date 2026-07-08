<?php

use App\Http\Controllers\HistoricalEntryController;
use App\Http\Controllers\NewEntryController;
use App\Http\Controllers\NewEntryImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OutreachController;
use App\Http\Controllers\Tool\AhrefsCleanerController;
use App\Http\Controllers\Tool\KeywordResearchController;
use App\Http\Controllers\Tool\ReferringDomainsController;
use App\Http\Controllers\Tool\TrafficDistributionController;
use App\Http\Controllers\Tool\WebScraperController;
use App\Http\Controllers\WebsiteImportController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForcePasswordChangeMiddleware;
use App\Http\Middleware\RestrictGuestToDomainsMiddleware;

/* ─────────────────────────────────────────────────────────────
 |  Controllers
 *───────────────────────────────────────────────────────────*/
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserFavoritesController;

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\CampaignCommentController;
use App\Http\Controllers\PublicationCommentController;
use App\Http\Controllers\CopyController;

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\StorageStatsController;

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
Route::middleware(['auth', 'verified', ForcePasswordChangeMiddleware::class, RestrictGuestToDomainsMiddleware::class])->group(function () {

    /*--------------------------------------------------------------
    | Dashboard
    --------------------------------------------------------------*/
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    /*--------------------------------------------------------------
| IMPORT
--------------------------------------------------------------*/
    Route::prefix('new-entries/import')->name('new_entries.import.')->group(function () {
        Route::get('/',        [NewEntryImportController::class, 'index'])->name('index');
        Route::post('/preview',[NewEntryImportController::class, 'preview'])->name('preview');
        Route::post('/commit', [NewEntryImportController::class, 'commit'])->name('commit');
        Route::get('/sample',  [NewEntryImportController::class, 'sample'])->name('sample'); // optional
    });

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
    | Companies
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'companies/data',  [CompanyController::class, 'getData'])->name('companies.data');
    Route::get('/companies/search',                 [CompanyController::class, 'search'])->name('companies.search');
    Route::resource('companies', CompanyController::class)->names([
        'index'   => 'companies.index',
        'store'   => 'companies.store',
        'update'  => 'companies.update',
        'destroy' => 'companies.destroy',
    ])->only(['index', 'store', 'update', 'destroy']);

    /*--------------------------------------------------------------
    | Clients
    --------------------------------------------------------------*/
    Route::match(['get','post'], 'clients/data',    [ClientsController::class, 'getData'])->name('clients.data');
    Route::post('/clients/{client}/restore',        [ClientsController::class, 'restore'])->name('clients.restore');

    Route::get('/clients/{id}/edit-ajax',           [ClientsController::class, 'editAjax'])->name('clients.editAjax');
    Route::get('/clients/ajax/{id}',                [ClientsController::class, 'showAjax'])->name('clients.showAjax');

    Route::resource('clients', ClientsController::class)->except(['show'])->names([
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
    /*--------------------------------------------------------------
    | My Favorites (guest-facing)
    --------------------------------------------------------------*/
    Route::get('/favorites', [WebsiteController::class, 'guestFavorites'])->name('favorites.index');

    /*--------------------------------------------------------------
    | Orders (guest-facing — own orders only)
    --------------------------------------------------------------*/
    Route::get('/orders',                       [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}',               [OrderController::class, 'show'])->name('orders.show');

    Route::get('/orders/cart/state',            [OrderController::class, 'cart'])->name('orders.cart');
    Route::post('/orders/cart/{website}',       [OrderController::class, 'addItem'])->name('orders.cart.add');
    Route::delete('/orders/cart/items/{item}',  [OrderController::class, 'removeItem'])->name('orders.cart.remove');
    Route::patch('/orders/cart/items/{item}',   [OrderController::class, 'setArticleType'])->name('orders.cart.set-type');
    Route::post('/orders/submit',               [OrderController::class, 'submit'])->name('orders.submit');

    Route::match(['get','post'], 'websites/data',   [WebsiteController::class, 'getData'])->name('websites.data');
    Route::post('/websites/{website}/restore',      [WebsiteController::class, 'restore'])->name('websites.restore');

    Route::get('/websites/export/csv',              [WebsiteController::class, 'exportCsv'])->name('websites.export.csv');
    Route::get('/websites/export/pdf',              [WebsiteController::class, 'exportPdf'])->name('websites.export.pdf');
    Route::post('/websites/{website}/favorite',     [FavoriteController::class, 'toggle'])->name('websites.favorites.toggle');
    Route::post('/websites/favorites/bulk',          [FavoriteController::class, 'bulk'])->name('websites.favorites.bulk');

    Route::post('/websites/outreach/preview', [OutreachController::class, 'preview'])
        ->middleware(['auth', 'verified'])->name('websites.outreach.preview');

    Route::post('/websites/outreach/send', [OutreachController::class, 'send'])
        ->middleware(['auth', 'verified'])->name('websites.outreach.send');

    /*--------------------------------------------------------------
| IMPORT
--------------------------------------------------------------*/

    Route::prefix('websites/import')->name('websites.import.')->group(function () {
        Route::get('/',        [WebsiteImportController::class, 'index'])->name('index');   // the page
        Route::get('/sample',  [WebsiteImportController::class, 'sample'])->name('sample'); // sample CSV
        Route::post('/preview',[WebsiteImportController::class, 'preview'])->name('preview');
        Route::post('/commit', [WebsiteImportController::class, 'commit'])->name('commit');
    });



    // routes/web.php
    Route::post('/websites/dataforseo/sync-selected',
        [WebsiteController::class, 'syncDataForSeoSelected'])
        ->name('websites.dataforseo.sync-selected');

    Route::post('/websites/bulk-convert-eur',
        [WebsiteController::class, 'bulkConvertToEur']
    )->name('websites.bulkConvertToEur');

    Route::post('/websites/bulk-update',   [WebsiteController::class,'bulkUpdate']
    )->name('websites.bulkUpdate');

    Route::post('/websites/undo',          [WebsiteController::class,'undo']
    )->name('websites.undo');

    Route::post('/websites/rollback',      [WebsiteController::class,'rollback']
    )->name('websites.rollback');


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


    Route::post('/new-entries/bulk', [NewEntryController::class,'bulkUpdate'])->name('new_entries.bulkUpdate');
    Route::post('/new-entries/rollback', [NewEntryController::class,'rollback'])->name('new_entries.rollback');
    Route::post('/new-entries/dataforseo/sync-selected', [NewEntryController::class,'syncDataForSeoSelected'])->name('new_entries.dataforseo.sync-selected');
    Route::get('/new-entries/export/csv', [NewEntryController::class,'exportCsv'])->name('new_entries.export.csv');

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

    Route::get('/storages/stats', [StorageStatsController::class, 'index'])
        ->name('storages.stats');


    Route::get('/storages/domain-preview', [StorageController::class, 'domainPreview'])->name('storages.domain_preview');

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

        //Ahrefs cleaner
        Route::get ('ahrefs-cleaner',     [AhrefsCleanerController::class, 'index'])->name('ahrefs.index');
        Route::post('ahrefs-cleaner/run', [AhrefsCleanerController::class, 'run'])->name('ahrefs.run');

        // Referring Domains
        Route::get ('referring-domains',        [ReferringDomainsController::class, 'index'])->name('referring_domains.index');
        Route::post('referring-domains/search', [ReferringDomainsController::class, 'search'])->name('referring_domains.search');

        // Traffic Distribution by Country
        Route::get ('traffic-distribution',        [TrafficDistributionController::class, 'index'])->name('traffic_distribution.index');
        Route::post('traffic-distribution/search', [TrafficDistributionController::class, 'search'])->name('traffic_distribution.search');

        // Keyword Research
        Route::get ('keyword-research',        [KeywordResearchController::class, 'index'])->name('keyword_research.index');
        Route::post('keyword-research/search', [KeywordResearchController::class, 'search'])->name('keyword_research.search');
    });

});




/*======================================================================
|  ADMIN‑ONLY  (User management)
=====================================================================*/
Route::middleware(['auth', 'verified', ForcePasswordChangeMiddleware::class, AdminMiddleware::class])->group(function () {

    Route::get('/admin/users/{id}/edit-ajax', [UserController::class, 'editAjax'])
        ->name('admin.users.editAjax');

    Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('admin.users.resetPassword');

    Route::get('/admin/users/data', [UserController::class, 'data'])
        ->name('admin.users.data');

    Route::get('/admin/users/{user}/favorites', [UserFavoritesController::class, 'index'])
        ->name('admin.users.favorites');
    Route::match(['get','post'], '/admin/users/{user}/favorites/data', [UserFavoritesController::class, 'data'])
        ->name('admin.users.favorites.data');
    Route::get('/admin/users/{user}/favorites/export/csv', [UserFavoritesController::class, 'exportCsv'])
        ->name('admin.users.favorites.export.csv');
    Route::get('/admin/users/{user}/favorites/export/pdf', [UserFavoritesController::class, 'exportPdf'])
        ->name('admin.users.favorites.export.pdf');

    /*--------------------------------------------------------------
    | Admin Orders
    --------------------------------------------------------------*/
    Route::get('/admin/orders',                       [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}',               [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/status',      [AdminOrderController::class, 'updateStatus'])->name('admin.orders.update-status');

    Route::resource('admin/users', UserController::class)->names([
        'index'   => 'admin.users.index',
        'create'  => 'admin.users.create',
        'store'   => 'admin.users.store',
        'edit'    => 'admin.users.edit',
        'update'  => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    /*--------------------------------------------------------------
    | Link Building CRM (admin-only) — Campaigns + Publications
    | Root-level URLs (no /crm prefix) to match the rest of the app.
    | Route NAMES keep the crm. namespace so they never collide with
    | the existing companies/clients route names and the sidebar
    | active-state stays correct. New lb_ tables; references shared
    | companies/clients/users.
    --------------------------------------------------------------*/
    // Campaigns — static/nested routes BEFORE the {campaign} show route
    Route::match(['get', 'post'], 'campaigns/data', [CampaignController::class, 'getData'])->name('crm.campaigns.data');
    Route::get('campaigns/websites-search', [PublicationController::class, 'websitesSearch'])->name('crm.publications.websitesSearch');
    Route::get('campaigns/{campaign}/edit-ajax',    [CampaignController::class, 'editAjax'])->name('crm.campaigns.editAjax');
    Route::put('campaigns/{campaign}/status',       [CampaignController::class, 'updateStatus'])->name('crm.campaigns.status');
    Route::put('campaigns/{campaign}/inline',       [CampaignController::class, 'inlineUpdate'])->name('crm.campaigns.inline');
    Route::get('companies/{company}/contacts',      [CampaignController::class, 'contactsForCompany'])->name('crm.company.contacts');

    Route::get('campaigns',               [CampaignController::class, 'index'])->name('crm.campaigns.index');
    Route::post('campaigns',              [CampaignController::class, 'store'])->name('crm.campaigns.store');
    Route::get('campaigns/{campaign}',    [CampaignController::class, 'show'])->name('crm.campaigns.show');
    Route::put('campaigns/{campaign}',    [CampaignController::class, 'update'])->name('crm.campaigns.update');
    Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('crm.campaigns.destroy');

    // Publications (Phase 3: {storage} binds to the storage row — the
    // publication IS a storage row; URLs keep the /publications prefix)
    Route::post('campaigns/{campaign}/publications',   [PublicationController::class, 'store'])->name('crm.publications.store');
    Route::get('campaigns/{campaign}/storage-search',  [PublicationController::class, 'searchStorages'])->name('crm.publications.storageSearch');
    Route::post('campaigns/{campaign}/link-publications', [PublicationController::class, 'linkExisting'])->name('crm.publications.link');
    Route::get('publications/{storage}/edit-ajax',     [PublicationController::class, 'editAjax'])->name('crm.publications.editAjax');
    Route::put('publications/{storage}/status',        [PublicationController::class, 'updateStatus'])->name('crm.publications.status');
    Route::put('publications/{storage}/inline',        [PublicationController::class, 'inlineUpdate'])->name('crm.publications.inline');
    Route::get('publications/{storage}/comments',              [PublicationCommentController::class, 'index'])->name('crm.publications.comments.index');
    Route::post('publications/{storage}/comments',             [PublicationCommentController::class, 'store'])->name('crm.publications.comments.store');
    Route::delete('publications/{storage}/comments/{comment}', [PublicationCommentController::class, 'destroy'])->name('crm.publications.comments.destroy');
    Route::put('publications/{storage}',                [PublicationController::class, 'update'])->name('crm.publications.update');
    Route::delete('publications/{storage}',             [PublicationController::class, 'destroy'])->name('crm.publications.destroy');

    // Comments (nested under campaigns so Apache only needs to whitelist "campaigns")
    Route::get('campaigns/{campaign}/comments',             [CampaignCommentController::class, 'index'])->name('crm.campaigns.comments.index');
    Route::post('campaigns/{campaign}/comments',            [CampaignCommentController::class, 'store'])->name('crm.campaigns.comments.store');
    Route::delete('campaigns/{campaign}/comments/{comment}', [CampaignCommentController::class, 'destroy'])->name('crm.campaigns.comments.destroy');

    // Admin-only CRM detail pages for the shared entities (root-level URLs)
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('crm.companies.show');
    Route::get('clients/{client}',    [ClientsController::class, 'show'])->name('crm.clients.show');
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
