<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MyUSController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PackageItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Web\WebsiteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// TEST ROUTE - Add this before admin routes to test
Route::get('/test-route', function () {
    return response("DIRECT ROUTE TEST - If you see this, routing works!", 200, ['Content-Type' => 'text/plain']);
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {

    // ===== DASHBOARD =====
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // Profile routes (always accessible to logged-in admin users)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===== IMPORTS =====
    Route::prefix('imports')->middleware('permission:imports.view')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('admin.import');
        Route::post('/', [ImportController::class, 'importUsers'])
            ->middleware('permission:imports.execute')
            ->name('admin.importUser');
    });

    // ===== PACKAGES =====
    Route::prefix('package')->middleware('permission:packages.view')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('admin.packages');
        
        // Special request response routes
        Route::post('/{package}/special-request/response', [\App\Http\Controllers\Admin\PackageSpecialRequestController::class, 'storeResponse'])
            ->middleware('permission:packages.edit')
            ->name('admin.packages.special-request.response');
        Route::post('/{package}/special-request/complete', [\App\Http\Controllers\Admin\PackageSpecialRequestController::class, 'markCompleted'])
            ->middleware('permission:packages.edit')
            ->name('admin.packages.special-request.complete');
        Route::delete('/special-request-photo/{photo}', [\App\Http\Controllers\Admin\PackageSpecialRequestController::class, 'deletePhoto'])
            ->middleware('permission:packages.edit')
            ->name('admin.packages.special-request-photo.delete');
        Route::get('/status-management', [PackageController::class, 'kanban'])
            ->middleware('permission:packages.kanban.view')
            ->name('admin.packages.kanban');
        Route::get('/create', [PackageController::class, 'create'])
            ->middleware('permission:packages.create')
            ->name('admin.packages.create');
        Route::post('/store', [PackageController::class, 'store'])
            ->middleware('permission:packages.create')
            ->name('admin.packages.store');
        // Barcode routes - must come before other {package} routes to avoid conflicts
        Route::get('/{package}/barcode/pdf', [\App\Http\Controllers\BarcodeController::class, 'downloadPDF'])
            ->middleware('permission:packages.view')
            ->name('admin.packages.barcode.pdf');
        Route::get('/{package}/barcode/view', [\App\Http\Controllers\BarcodeController::class, 'viewPDF'])
            ->middleware('permission:packages.view')
            ->name('admin.packages.barcode.view');
        Route::get('/{package}/barcode/zpl', [\App\Http\Controllers\BarcodeController::class, 'downloadZPL'])
            ->middleware('permission:packages.view')
            ->name('admin.packages.barcode.zpl');
        Route::get('/{package}/barcode/image', [\App\Http\Controllers\BarcodeController::class, 'image'])
            ->middleware('permission:packages.view')
            ->name('admin.packages.barcode.image');
        Route::get('/{package}/invoices/merged', [PackageController::class, 'downloadMergedInvoices'])
            ->middleware('permission:packages.view')
            ->name('admin.packages.invoices.merged');
        
        Route::get('/edit/{package}', [PackageController::class, 'edit'])
            ->middleware('permission:packages.update')
            ->name('admin.packages.edit');
        Route::put('/update/{package}', [PackageController::class, 'update'])
            ->middleware('permission:packages.update')
            ->name('admin.packages.update');
        Route::delete('/delete/{package}', [PackageController::class, 'destroy'])
            ->middleware('permission:packages.delete')
            ->name('admin.packages.delete');
        Route::put('/{package}/status', [PackageController::class, 'updateStatus'])
            ->middleware('permission:packages.kanban.update')
            ->name('admin.packages.updateStatus');
        Route::put('/update-note/{package}', [PackageController::class, 'updateNote'])
            ->middleware('permission:packages.notes.update')
            ->name('admin.packages.updateNote');
    });

    // ===== PACKAGE ITEMS =====
    Route::prefix('package-items')->middleware('permission:packages.items.view')->group(function () {
        Route::post('/', [PackageItemController::class, 'store'])
            ->middleware('permission:packages.items.create')
            ->name('admin.package-items.store');
        Route::get('/{packageItem}', [PackageItemController::class, 'show'])
            ->name('admin.package-items.show');
        Route::put('/{packageItem}', [PackageItemController::class, 'update'])
            ->middleware('permission:packages.items.update')
            ->name('admin.package-items.update');
        Route::delete('/{packageItem}', [PackageItemController::class, 'destroy'])
            ->middleware('permission:packages.items.delete')
            ->name('admin.package-items.destroy');
    });

    // ===== CUSTOMERS (Admin managing customers) =====
    Route::prefix('customers')->middleware('permission:customers.view')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.customers');
        Route::get('/create', [UserController::class, 'create'])
            ->middleware('permission:customers.create')
            ->name('admin.customers.create');
        Route::post('/store', [UserController::class, 'store'])
            ->middleware('permission:customers.create')
            ->name('admin.customers.store');
        Route::get('/edit/{customer}', [UserController::class, 'edit'])
            ->middleware('permission:customers.update')
            ->name('admin.customers.edit');
        Route::put('/update/{customer}', [UserController::class, 'update'])
            ->middleware('permission:customers.update')
            ->name('admin.customers.update');
        Route::delete('/{customer}', [UserController::class, 'destroy'])
            ->middleware('permission:customers.delete')
            ->name('admin.customers.destroy');

        // Customer transactions sub-routes
        Route::prefix('transactions')->middleware('permission:customers.transactions.view')->group(function () {
            Route::get('/{customer}', [TransactionController::class, 'userTransaction'])
                ->name('admin.customers.transactions');
            Route::put('/refund/{transaction}', [TransactionController::class, 'refundTransaction'])
                ->middleware('permission:transactions.refunds.process')
                ->name('admin.customers.refundTransaction');
        });

        // Customer packages sub-routes
        Route::prefix('packages')->group(function () {
            Route::get('/{customer}', [PackageController::class, 'getUserPackages'])
                ->middleware('permission:customers.packages.view')
                ->name('admin.customers.packages');
        });
    });

    // ===== TRANSACTIONS =====
    Route::prefix('transactions')->middleware('permission:transactions.view')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('admin.transactions.allTransactions');
    });

    // ===== SHIPMENTS =====
    Route::prefix('shipments')->middleware('permission:shipments.view')->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('admin.shipments');
        Route::get('/requests', [ShipmentController::class, 'requests'])->name('admin.shipments.requests');
        Route::get('edit/{ship}', [ShipmentController::class, 'edit'])
            ->middleware('permission:shipments.update')
            ->name('admin.shipments.edit');
        Route::post('/update/{ship}', [ShipmentController::class, 'update'])
            ->middleware('permission:shipments.update')
            ->name('admin.shipments.update');
        Route::post('/update-status/{ship}', [ShipmentController::class, 'updateStatus'])
            ->middleware('permission:shipments.status.update')
            ->name('admin.shipments.update-status');
        Route::get('/packages/{ship}', [ShipmentController::class, 'shipPackages'])
            ->name('admin.shipments.packages');

        // Tracking actions (consolidated from order-tracking module)
        Route::get('/tracking/{ship}', [ShipmentController::class, 'showTracking'])
            ->name('admin.shipments.tracking');
        Route::post('/tracking/{ship}/refresh', [ShipmentController::class, 'refreshTracking'])
            ->middleware('permission:shipments.tracking.refresh')
            ->name('admin.shipments.tracking.refresh');
        Route::post('/tracking/{ship}/event', [ShipmentController::class, 'addTrackingEvent'])
            ->middleware('permission:shipments.tracking.event')
            ->name('admin.shipments.tracking.addEvent');

        // Carrier operations (consolidated from order-tracking module)
        Route::post('/{ship}/retry-carrier', [ShipmentController::class, 'retryCarrierSubmission'])
            ->middleware('permission:shipments.carrier.retry')
            ->name('admin.shipments.retryCarrier');
        Route::post('/{ship}/manual-tracking', [ShipmentController::class, 'setManualTracking'])
            ->middleware('permission:shipments.carrier.manual')
            ->name('admin.shipments.manualTracking');
        Route::post('/{ship}/sync-carrier', [ShipmentController::class, 'syncFromCarrier'])
            ->middleware('permission:shipments.carrier.sync')
            ->name('admin.shipments.syncCarrier');

        // Label routes
        Route::get('/{ship}/label/view', [ShipmentController::class, 'viewLabel'])
            ->name('admin.shipments.label.view');
        Route::get('/{ship}/label/download', [ShipmentController::class, 'downloadLabel'])
            ->name('admin.shipments.label.download');
        Route::get('/{ship}/label/zpl', [ShipmentController::class, 'viewLabelZPL'])
            ->name('admin.shipments.label.zpl');
        Route::get('/{ship}/invoice/view', [ShipmentController::class, 'viewInvoice'])
            ->name('admin.shipments.invoice.view');
        Route::get('/{ship}/invoice/download', [ShipmentController::class, 'downloadInvoice'])
            ->name('admin.shipments.invoice.download');

        // Commercial Invoice preview (for testing PDF generation without carrier submission)
        Route::get('/{ship}/invoice/preview', [ShipmentController::class, 'previewCommercialInvoice'])
            ->name('admin.shipments.invoice.preview');

        // Merchant invoices - merged master PDF
        Route::get('/{ship}/merchant-invoices/merged', [ShipmentController::class, 'downloadMergedMerchantInvoices'])
            ->name('admin.shipments.merchant-invoices.merged');

        // Delete shipment
        Route::delete('/delete/{ship}', [ShipmentController::class, 'destroy'])
            ->middleware('permission:shipments.delete')
            ->name('admin.shipments.delete');

        // Operator workflow actions
        Route::post('/{ship}/mark-packed', [ShipmentController::class, 'markPacked'])
            ->middleware('permission:shipments.update')
            ->name('admin.shipments.markPacked');
        Route::post('/{ship}/mark-picked-up', [ShipmentController::class, 'markPickedUp'])
            ->middleware('permission:shipments.update')
            ->name('admin.shipments.markPickedUp');

        // Operator shipment creation
        Route::get('/create', [ShipmentController::class, 'create'])
            ->middleware('permission:shipments.create')
            ->name('admin.shipments.create');
        Route::post('/store', [ShipmentController::class, 'store'])
            ->middleware('permission:shipments.create')
            ->name('admin.shipments.store');
        Route::get('/customers/search', [ShipmentController::class, 'searchCustomers'])
            ->name('admin.shipments.searchCustomers');
        Route::get('/customers/{customer}/available-packages', [ShipmentController::class, 'getAvailablePackages'])
            ->name('admin.shipments.availablePackages');
        Route::post('/get-rates', [ShipmentController::class, 'getRates'])
            ->name('admin.shipments.getRates');
    });

    // ===== MYUS INTEGRATION =====
    Route::prefix('myus')->middleware('permission:packages.view')->group(function () {
        // View packages from MyUS
        Route::get('/packages', [MyUSController::class, 'packages'])
            ->name('admin.myus.packages');
        
        // View shipments from MyUS
        Route::get('/shipments', [MyUSController::class, 'shipments'])
            ->name('admin.myus.shipments');
        
        // Get package details
        Route::get('/packages/{packageId}', [MyUSController::class, 'packageDetails'])
            ->name('admin.myus.package.details');
        
        // API endpoints (for AJAX calls)
        Route::prefix('api')->group(function () {
            Route::get('/packages', [MyUSController::class, 'apiPackages'])
                ->name('admin.myus.api.packages');
            Route::get('/shipments', [MyUSController::class, 'apiShipments'])
                ->name('admin.myus.api.shipments');
        });
        
        // Server test endpoint (remove after testing or protect with auth)
        Route::get('/test', function () {
            ob_start();
            include base_path('test_myus_server.php');
            return ob_get_clean();
        })->name('admin.myus.test');
    });

    // ===== CARRIER SERVICES & ADDONS =====
    Route::prefix('carrier-services')->middleware('permission:shipments.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\CarrierServicesController::class, 'index'])->name('admin.carrier-services.index');

        // Services
        Route::post('/services', [\App\Http\Controllers\CarrierServicesController::class, 'storeService'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.storeService');
        Route::patch('/services/{carrierService}', [\App\Http\Controllers\CarrierServicesController::class, 'updateService'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.updateService');
        Route::post('/services/{carrierService}/toggle', [\App\Http\Controllers\CarrierServicesController::class, 'toggleServiceStatus'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.toggleService');
        Route::delete('/services/{carrierService}', [\App\Http\Controllers\CarrierServicesController::class, 'destroyService'])
            ->middleware('permission:shipments.delete')
            ->name('admin.carrier-services.destroyService');

        // Addons
        Route::post('/addons', [\App\Http\Controllers\CarrierServicesController::class, 'storeAddon'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.storeAddon');
        Route::patch('/addons/{carrierAddon}', [\App\Http\Controllers\CarrierServicesController::class, 'updateAddon'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.updateAddon');
        Route::post('/addons/{carrierAddon}/toggle', [\App\Http\Controllers\CarrierServicesController::class, 'toggleAddonStatus'])
            ->middleware('permission:shipments.update')
            ->name('admin.carrier-services.toggleAddon');
        Route::delete('/addons/{carrierAddon}', [\App\Http\Controllers\CarrierServicesController::class, 'destroyAddon'])
            ->middleware('permission:shipments.delete')
            ->name('admin.carrier-services.destroyAddon');
    });

    // ===== COMMISSION SETTINGS =====
    // Only accessible by super-admin or admin role
    Route::prefix('commission')
        ->middleware(\App\Http\Middleware\EnsureSuperAdminOrAdmin::class)
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CommissionController::class, 'index'])
                ->name('admin.commission.index');
            Route::put('/', [\App\Http\Controllers\Admin\CommissionController::class, 'update'])
                ->name('admin.commission.update');
        });

    // ===== RATE MANAGEMENT ===== ->middleware('permission:rates.manage')
    Route::prefix('rate-management')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\RateManagementController::class, 'index'])
            ->name('admin.rate-management.index');
        Route::post('/rules', [\App\Http\Controllers\Admin\RateManagementController::class, 'storeRule'])
            ->name('admin.rate-management.store');
        Route::put('/rules/{rule}', [\App\Http\Controllers\Admin\RateManagementController::class, 'updateRule'])
            ->name('admin.rate-management.update');
        Route::delete('/rules/{rule}', [\App\Http\Controllers\Admin\RateManagementController::class, 'destroyRule'])
            ->name('admin.rate-management.destroy');
        Route::post('/rules/{rule}/toggle', [\App\Http\Controllers\Admin\RateManagementController::class, 'toggleRule'])
            ->name('admin.rate-management.toggle');
        Route::post('/simulate', [\App\Http\Controllers\Admin\RateManagementController::class, 'simulateRate'])
            ->name('admin.rate-management.simulate');
    });

    // ===== COUPONS =====
    Route::prefix('coupons')->middleware('permission:coupons.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\CouponController::class, 'index'])->name('admin.coupons.index');
        Route::get('/create', [\App\Http\Controllers\CouponController::class, 'create'])
            ->middleware('permission:coupons.create')
            ->name('admin.coupons.create');
        Route::post('/store', [\App\Http\Controllers\CouponController::class, 'store'])
            ->middleware('permission:coupons.create')
            ->name('admin.coupons.store');
        Route::get('/edit/{coupon}', [\App\Http\Controllers\CouponController::class, 'edit'])
            ->middleware('permission:coupons.update')
            ->name('admin.coupons.edit');
        Route::put('/update/{coupon}', [\App\Http\Controllers\CouponController::class, 'update'])
            ->middleware('permission:coupons.update')
            ->name('admin.coupons.update');
        Route::delete('/delete/{coupon}', [\App\Http\Controllers\CouponController::class, 'destroy'])
            ->middleware('permission:coupons.delete')
            ->name('admin.coupons.destroy');
        Route::put('/{coupon}/toggle-status', [\App\Http\Controllers\CouponController::class, 'toggleStatus'])
            ->middleware('permission:coupons.toggle.update')
            ->name('admin.coupons.toggle-status');
        Route::get('/stats', [\App\Http\Controllers\CouponController::class, 'usageStats'])
            ->middleware('permission:coupons.stats.view')
            ->name('admin.coupons.stats');
        Route::post('/generate-code', [\App\Http\Controllers\CouponController::class, 'generateCode'])
            ->middleware('permission:coupons.create')
            ->name('admin.coupons.generate-code');
        Route::get('/search-customers', [\App\Http\Controllers\CouponController::class, 'searchCustomers'])
            ->middleware('permission:coupons.create')
            ->name('admin.coupons.search-customers');
    });

    // ===== LOYALTY PROGRAM =====
    Route::prefix('loyalty')->middleware('permission:loyalty.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\LoyaltyController::class, 'index'])->name('admin.loyalty.index');
        Route::get('/rules', [\App\Http\Controllers\LoyaltyController::class, 'rules'])
            ->middleware('permission:loyalty.rules.view')
            ->name('admin.loyalty.rules');
        Route::post('/rules/store', [\App\Http\Controllers\LoyaltyController::class, 'storeRule'])
            ->middleware('permission:loyalty.rules.create')
            ->name('admin.loyalty.rules.store');
        Route::post('/rules/{rule}/update', [\App\Http\Controllers\LoyaltyController::class, 'updateRule'])
            ->middleware('permission:loyalty.rules.update')
            ->name('admin.loyalty.rules.update');
        Route::delete('/rules/{rule}/delete', [\App\Http\Controllers\LoyaltyController::class, 'destroyRule'])
            ->middleware('permission:loyalty.rules.delete')
            ->name('admin.loyalty.rules.destroy');
        Route::put('/rules/{rule}/toggle-status', [\App\Http\Controllers\LoyaltyController::class, 'toggleRuleStatus'])
            ->middleware('permission:loyalty.rules.update')
            ->name('admin.loyalty.rules.toggle-status');
        Route::get('/transactions', [\App\Http\Controllers\LoyaltyController::class, 'transactions'])
            ->middleware('permission:loyalty.transactions.view')
            ->name('admin.loyalty.transactions');
        Route::get('/customers', [\App\Http\Controllers\LoyaltyController::class, 'users'])
            ->middleware('permission:loyalty.users.view')
            ->name('admin.loyalty.customers');

        // Tier Management
        Route::get('/tiers', [\App\Http\Controllers\LoyaltyController::class, 'tiers'])
            ->middleware('permission:loyalty.tiers.view')
            ->name('admin.loyalty.tiers');
        Route::post('/tiers/store', [\App\Http\Controllers\LoyaltyController::class, 'storeTier'])
            ->middleware('permission:loyalty.tiers.create')
            ->name('admin.loyalty.tiers.store');
        Route::post('/tiers/{tier}/update', [\App\Http\Controllers\LoyaltyController::class, 'updateTier'])
            ->middleware('permission:loyalty.tiers.update')
            ->name('admin.loyalty.tiers.update');
        Route::delete('/tiers/{tier}/delete', [\App\Http\Controllers\LoyaltyController::class, 'destroyTier'])
            ->middleware('permission:loyalty.tiers.delete')
            ->name('admin.loyalty.tiers.destroy');

        // Point Management
        Route::post('/customers/{customer}/adjust-points', [\App\Http\Controllers\LoyaltyController::class, 'adjustPoints'])
            ->middleware('permission:loyalty.points.adjust')
            ->name('admin.loyalty.adjust-points');

        // Referral Management
        Route::get('/referrals', [\App\Http\Controllers\LoyaltyController::class, 'referrals'])
            ->middleware('permission:loyalty.referrals.view')
            ->name('admin.loyalty.referrals');
    });

    // ===== ORDER TRACKING (DEPRECATED - Routes consolidated into /shipments) =====
    // Old routes redirect to new shipments module for backwards compatibility
    Route::prefix('order-tracking')->middleware('permission:shipments.view')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.shipments'))->name('admin.tracking.index');
        Route::get('/search', fn() => redirect()->route('admin.shipments'))->name('admin.tracking.search');
        Route::get('/{ship}', fn($ship) => redirect()->route('admin.shipments.tracking', $ship))->name('admin.tracking.show');
    });

    // ===== CHANGE REQUESTS =====
    Route::prefix('change-requests')->middleware('permission:change-requests.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PackageChangeRequestController::class, 'index'])->name('admin.change-requests.index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\PackageChangeRequestController::class, 'show'])->name('admin.change-requests.show');
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\PackageChangeRequestController::class, 'approve'])
            ->middleware('permission:change-requests.approve.execute')
            ->name('admin.change-requests.approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Admin\PackageChangeRequestController::class, 'reject'])
            ->middleware('permission:change-requests.reject.execute')
            ->name('admin.change-requests.reject');
        Route::post('/bulk-action', [\App\Http\Controllers\Admin\PackageChangeRequestController::class, 'bulkAction'])
            ->middleware('permission:change-requests.bulk.execute')
            ->name('admin.change-requests.bulk');
    });

    // ===== ACCESS CONTROL ROUTES =====

    // Unified User Management Page (combines Roles & System Users)
    Route::get('/user-management', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])
        ->middleware('permission:system-users.view')
        ->name('admin.user-management.index');

    // Role Management Routes (accessed via user-management page)
    Route::prefix('roles')->middleware('permission:roles.view')->group(function () {
        Route::get('/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->middleware('permission:roles.create')->name('admin.roles.create');
        Route::post('/store', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->middleware('permission:roles.create')->name('admin.roles.store');
        Route::get('/edit/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->middleware('permission:roles.update')->name('admin.roles.edit');
        Route::put('/update/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->middleware('permission:roles.update')->name('admin.roles.update');
        Route::delete('/delete/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('admin.roles.delete');
    });

    // System User Management Routes
    Route::prefix('system-users')->middleware('permission:system-users.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SystemUserController::class, 'index'])->name('admin.system-users.index');
        Route::get('/create', [\App\Http\Controllers\Admin\SystemUserController::class, 'create'])->middleware('permission:system-users.create')->name('admin.system-users.create');
        Route::post('/store', [\App\Http\Controllers\Admin\SystemUserController::class, 'store'])->middleware('permission:system-users.create')->name('admin.system-users.store');
        Route::get('/edit/{user}', [\App\Http\Controllers\Admin\SystemUserController::class, 'edit'])->middleware('permission:system-users.update')->name('admin.system-users.edit');
        Route::put('/update/{user}', [\App\Http\Controllers\Admin\SystemUserController::class, 'update'])->middleware('permission:system-users.update')->name('admin.system-users.update');
        Route::put('/toggle-status/{user}', [\App\Http\Controllers\Admin\SystemUserController::class, 'toggleStatus'])->middleware('permission:system-users.status.toggle')->name('admin.system-users.toggle-status');
        Route::delete('/delete/{user}', [\App\Http\Controllers\Admin\SystemUserController::class, 'destroy'])->middleware('permission:system-users.delete')->name('admin.system-users.delete');
    });

    // ===== WAREHOUSE MANAGEMENT =====
    Route::prefix('warehouses')->middleware('permission:warehouses.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\WarehouseController::class, 'index'])->name('admin.warehouses.index');
        Route::get('/create', [\App\Http\Controllers\Admin\WarehouseController::class, 'create'])->middleware('permission:warehouses.create')->name('admin.warehouses.create');
        Route::post('/store', [\App\Http\Controllers\Admin\WarehouseController::class, 'store'])->middleware('permission:warehouses.create')->name('admin.warehouses.store');
        Route::get('/edit/{warehouse}', [\App\Http\Controllers\Admin\WarehouseController::class, 'edit'])->middleware('permission:warehouses.update')->name('admin.warehouses.edit');
        Route::put('/update/{warehouse}', [\App\Http\Controllers\Admin\WarehouseController::class, 'update'])->middleware('permission:warehouses.update')->name('admin.warehouses.update');
        Route::put('/{warehouse}/set-default', [\App\Http\Controllers\Admin\WarehouseController::class, 'setDefault'])->middleware('permission:warehouses.default.set')->name('admin.warehouses.set-default');
        Route::put('/{warehouse}/toggle-active', [\App\Http\Controllers\Admin\WarehouseController::class, 'toggleActive'])->middleware('permission:warehouses.status.toggle')->name('admin.warehouses.toggle-active');
    });

    // Warehouse API (for dropdowns)
    Route::get('/api/warehouses/active', [\App\Http\Controllers\Admin\WarehouseController::class, 'getActive'])->name('api.warehouses.active');
});

// website routes - These should NOT use Inertia
Route::get("/", [WebsiteController::class, "index"])->name('web.home')->middleware('web');
Route::get("/how-it-works", [WebsiteController::class, "howItWorks"])->name('web.how-it-works');
Route::get("/calculator", [WebsiteController::class, "calculator"])->name('web.calculator');
Route::get("/contact", [WebsiteController::class, "contact"])->name('web.contact');
Route::get('/about', [WebsiteController::class, 'about'])->name('web.about');
Route::get('/faqs', [WebsiteController::class, 'faqs'])->name('web.faqs');
Route::get('/term', [WebsiteController::class, 'terms'])->name('web.terms');
Route::get('/privacy', [WebsiteController::class, 'privacy'])->name('web.privacy');

// routes/web.php
Route::post('/calculate-shipping', [WebsiteController::class, 'calculate']);

// Address API routes (for country/state dropdowns)
Route::prefix('api/address')->group(function () {
    Route::get('/countries', [\App\Http\Controllers\Api\AddressController::class, 'countries'])->name('api.address.countries');
    Route::get('/states/{countryCode}', [\App\Http\Controllers\Api\AddressController::class, 'states'])->name('api.address.states');
    Route::post('/normalize', [\App\Http\Controllers\Api\AddressController::class, 'normalize'])->name('api.address.normalize');
    Route::get('/validate-country/{code}', [\App\Http\Controllers\Api\AddressController::class, 'validateCountry'])->name('api.address.validate-country');
});

// Location API routes (for cascading address dropdowns)
Route::prefix('api/locations')->group(function () {
    Route::get('/countries', [\App\Http\Controllers\LocationController::class, 'countries'])->name('api.locations.countries');
    Route::get('/states/{country}', [\App\Http\Controllers\LocationController::class, 'states'])->name('api.locations.states');
    Route::get('/cities/{state}', [\App\Http\Controllers\LocationController::class, 'cities'])->name('api.locations.cities');
    Route::get('/lookup', [\App\Http\Controllers\LocationController::class, 'lookup'])->name('api.locations.lookup');
});

// HS Code Lookup API
Route::prefix('api/hs-codes')->group(function () {
    Route::post('/lookup', [\App\Http\Controllers\Api\HSCodeController::class, 'lookup'])->name('api.hs-codes.lookup');
});

// ===== ARTISAN COMMANDS (Public routes with token protection) =====
// Usage: /artisan/{command}?token=YOUR_SECRET_TOKEN
// Set ARTISAN_TOKEN in .env file for security
Route::prefix('artisan')->group(function () {
    Route::get('/optimize-clear', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('optimize:clear');
        return response()->json([
            'success' => true,
            'message' => 'Optimize clear completed',
            'output' => Artisan::output()
        ]);
    })->name('artisan.optimize-clear');

    Route::get('/storage-link', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        try {
            Artisan::call('storage:link');
            return response()->json([
                'success' => true,
                'message' => 'Storage link created successfully',
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Storage link failed',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('artisan.storage-link');

    Route::get('/config-cache', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('config:cache');
        return response()->json([
            'success' => true,
            'message' => 'Config cached successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.config-cache');

    Route::get('/config-clear', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('config:clear');
        return response()->json([
            'success' => true,
            'message' => 'Config cleared successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.config-clear');

    Route::get('/route-cache', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('route:cache');
        return response()->json([
            'success' => true,
            'message' => 'Routes cached successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.route-cache');

    Route::get('/route-clear', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('route:clear');
        return response()->json([
            'success' => true,
            'message' => 'Routes cleared successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.route-clear');

    Route::get('/view-cache', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('view:cache');
        return response()->json([
            'success' => true,
            'message' => 'Views cached successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.view-cache');

    Route::get('/view-clear', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('view:clear');
        return response()->json([
            'success' => true,
            'message' => 'Views cleared successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.view-clear');

    Route::get('/cache-clear', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('cache:clear');
        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.cache-clear');

    Route::get('/migrate', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        Artisan::call('migrate', ['--force' => true]);
        return response()->json([
            'success' => true,
            'message' => 'Migrations completed',
            'output' => Artisan::output()
        ]);
    })->name('artisan.migrate');

    Route::get('/migrate-fresh', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Extra protection for destructive commands
        if (app()->environment('production')) {
            return response()->json(['error' => 'This command is disabled in production'], 403);
        }
        
        Artisan::call('migrate:fresh', ['--force' => true]);
        return response()->json([
            'success' => true,
            'message' => 'Database refreshed',
            'output' => Artisan::output()
        ]);
    })->name('artisan.migrate-fresh');

    Route::get('/db-seed', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $class = request()->query('class');
        if ($class) {
            Artisan::call('db:seed', ['--class' => $class, '--force' => true]);
        } else {
            Artisan::call('db:seed', ['--force' => true]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Database seeded successfully',
            'output' => Artisan::output()
        ]);
    })->name('artisan.db-seed');

    Route::get('/composer-install', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Check if composer is available
        $composerPath = base_path('composer.phar');
        $composerCommand = file_exists($composerPath) 
            ? 'php ' . $composerPath 
            : 'composer';
        
        // Run composer install
        $output = [];
        $returnVar = 0;
        exec("{$composerCommand} install --no-dev --optimize-autoloader 2>&1", $output, $returnVar);
        
        if ($returnVar !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Composer install failed',
                'output' => implode("\n", $output),
                'return_code' => $returnVar
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Composer install completed successfully',
            'output' => implode("\n", $output)
        ]);
    })->name('artisan.composer-install');

    Route::get('/composer-update', function () {
        $token = request()->query('token');
        if ($token !== config('app.artisan_token', env('ARTISAN_TOKEN'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Check if composer is available
        $composerPath = base_path('composer.phar');
        $composerCommand = file_exists($composerPath) 
            ? 'php ' . $composerPath 
            : 'composer';
        
        // Run composer update
        $output = [];
        $returnVar = 0;
        exec("{$composerCommand} update --no-dev --optimize-autoloader 2>&1", $output, $returnVar);
        
        if ($returnVar !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Composer update failed',
                'output' => implode("\n", $output),
                'return_code' => $returnVar
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Composer update completed successfully',
            'output' => implode("\n", $output)
        ]);
    })->name('artisan.composer-update');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/customer.php';
