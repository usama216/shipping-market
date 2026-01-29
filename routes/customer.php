<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Customer\EmailVerificationController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\PackageChangeRequestController;
use App\Http\Controllers\Customer\PaymentMethodController;
use App\Http\Controllers\Customer\PayPalCheckoutController;
use App\Http\Controllers\Customer\ShipController;
use App\Http\Controllers\Customer\ShippingPreferenceController;
use App\Http\Controllers\Customer\SuiteController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerAddressController;
use Inertia\Inertia;

// Customer Email Verification - Route OUTSIDE auth middleware (accessible from email link)
Route::prefix('customer')->group(function () {
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('customer.verification.verify');
});

// Customer Email Verification Routes (require authentication)
Route::prefix('customer')->middleware(['auth:customer'])->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])
        ->name('customer.verification.notice');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('customer.verification.send');
});

// Main customer routes - require verified email
Route::prefix('customer')->middleware(['auth:customer', 'customer', 'customer.verified'])->group(function () {

    Route::get('/dashboard', [SuiteController::class, 'index'])->name('customer.dashboard');

    Route::prefix('suite')->group(function () {
        Route::get('/action-required', [SuiteController::class, 'actionRequired'])->name('customer.suiteActionRequired');

        Route::get('/in-review', [SuiteController::class, 'inReview'])->name('customer.suite.inReview');

        Route::get('/ready-to-send', [SuiteController::class, 'readyToSend'])->name('customer.suite.readyToSend');

        Route::get('/view-all', [SuiteController::class, 'viewAll'])->name('customer.suite.viewAll');

        Route::post('/package/add-note', [SuiteController::class, 'addNote'])->name('customer.packageAddNote');
        Route::post('/package/upload-invoices', [SuiteController::class, 'uploadInvoices'])->name('customers.packageUploadInvoices');
        Route::post('/package/mark-complete', [SuiteController::class, 'markAsComplete'])->name('customers.packageMarkComplete');
        Route::get('/package/photos', [SuiteController::class, 'getPackagePhotos'])->name('customers.packageGetPhotos');
        Route::post('/package/special-request', [SuiteController::class, 'setSpecialRequest'])->name('customer.packageSetSpecialRequest');

        Route::post('/calculate-estimated-shipment', [SuiteController::class, 'calculateEstimatedShipment'])->name('admin.packages.calculateEstimatedShipment');
        
        // Barcode routes for customers
        Route::get('/package/{package}/barcode/pdf', [\App\Http\Controllers\BarcodeController::class, 'downloadPDF'])
            ->name('customer.packages.barcode.pdf');
        Route::get('/package/{package}/barcode/view', [\App\Http\Controllers\BarcodeController::class, 'viewPDF'])
            ->name('customer.packages.barcode.view');
        Route::get('/package/{package}/barcode/zpl', [\App\Http\Controllers\BarcodeController::class, 'downloadZPL'])
            ->name('customer.packages.barcode.zpl');
        Route::get('/package/{package}/barcode/image', [\App\Http\Controllers\BarcodeController::class, 'image'])
            ->name('customer.packages.barcode.image');
    });

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('account-setting')->group(function () {

        Route::get('/profile', [ProfileController::class, 'customerProfile'])->name('customer.account.profile');

        Route::patch('/profile', [ProfileController::class, 'update'])->name('customer.account.update');

        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('customer.account.destroy');

        Route::put('password', [PasswordController::class, 'update'])->name('customer.account.password.update');


        Route::get('/address-book', [CustomerAddressController::class, 'index'])->name('customer.account.addressBook');

        Route::prefix('addresses')->group(function () {
            Route::post('/', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
            Route::put('/{address}', [CustomerAddressController::class, 'update'])->name('customer.addresses.update');
            Route::delete('/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.addresses.destroy');
            Route::put('/{address}/set-default', [CustomerAddressController::class, 'setDefault'])->name('customer.addresses.setDefault');
        });

        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'paymentMethods'])->name('customer.payment.paymentMethods');

            Route::post('/add-card', [PaymentMethodController::class, 'storeCard'])->name('customer.card.add');

            Route::put('/customer/card/set-default/{id}', [PaymentMethodController::class, 'setDefault'])->name('customer.card.setDefault');
            Route::delete('/customer/card/{id}', [PaymentMethodController::class, 'destroy'])->name('customer.card.delete');

            Route::put('/card/update/{id}', [PaymentMethodController::class, 'updateCard'])->name('customer.card.update');
        });

        Route::prefix('shipping-preference')->group(function () {

            Route::get('/', [ShippingPreferenceController::class, 'index'])->name('customer.shippingPreferences.preference');

            Route::post('/preference-address', [ShippingPreferenceController::class, 'setPreferAddress'])->name('customer.preference.changeAddress');

            Route::post('/preferences-save-changes', [ShippingPreferenceController::class, 'saveChangePreferences'])->name('customer.preferences.saveChange');
        });
    });

    Route::prefix('shipment')->group(function () {

        Route::get('/{ship}', [ShipController::class, 'index'])->name('customer.shipment.index');
        Route::post('/create', [ShipController::class, 'createShipment'])->name('customer.shipment.create');

        Route::delete('/packages/delete/{id}/{packageId}', [ShipController::class, 'deletePackageFromShip'])->name('customer.ship.packages.delete');

        Route::post('calculate-shipping-cost', [ShipController::class, 'calculateShippingCost'])->name('customer.shipment.calculateShippingCost');
        Route::post('get-all-rates', [ShipController::class, 'getAllShippingRates'])->name('customer.shipment.getAllRates');


        Route::post('/packages/add-national-id/{id}', [ShipController::class, 'addNationalId'])->name('customer.ship.packages.nationalId');

        Route::post('checkout', [ShipController::class, 'checkout'])->name('customer.ship.checkout');

        Route::get('/ship-success/{shipId}', [ShipController::class, 'successPage'])->name('customer.shipment.success');

        Route::get("/my/shipments", [ShipController::class, 'myShipments'])->name('customer.shipment.myShipments');
        Route::get("/details/shipments/{ship}", [ShipController::class, 'viewShipment'])->name('customer.shipment.details');

        // Commercial invoice routes (for customs purposes)
        Route::get('/invoice/download/{ship}', [\App\Http\Controllers\Customer\InvoiceController::class, 'download'])->name('customer.shipment.invoice.download');
        Route::get('/invoice/view/{ship}', [\App\Http\Controllers\Customer\InvoiceController::class, 'view'])->name('customer.shipment.invoice.view');
        
        // Merchant invoice routes (invoices uploaded by customer)
        Route::get('/merchant-invoice/download/{ship}', [\App\Http\Controllers\Customer\MerchantInvoiceController::class, 'downloadZip'])->name('customer.shipment.merchant-invoice.download');
        Route::get('/merchant-invoice/file/{ship}/{invoiceFile}', [\App\Http\Controllers\Customer\MerchantInvoiceController::class, 'download'])->name('customer.shipment.merchant-invoice.file');

        // PayPal checkout routes
        Route::post('/paypal/initiate', [PayPalCheckoutController::class, 'initiatePayPalCheckout'])->name('customer.checkout.paypal.initiate');
        Route::get('/paypal/return', [PayPalCheckoutController::class, 'handlePayPalReturn'])->name('customer.checkout.paypal.return');

    });

    Route::get('/checkout-page', function () {
        return Inertia::render('Customers/Checkout/Report');
    })->name('customers.checkoutPage');

    // Coupon routes
    Route::prefix('coupons')->group(function () {
        Route::post('/validate', [\App\Http\Controllers\Customer\CouponController::class, 'validate'])->name('customer.coupons.validate');
        Route::post('/auto-apply', [\App\Http\Controllers\Customer\CouponController::class, 'autoApply'])->name('customer.coupons.auto-apply');
        Route::get('/history', [\App\Http\Controllers\Customer\CouponController::class, 'history'])->name('customer.coupons.history');
    });

    // Loyalty routes
    Route::prefix('loyalty')->group(function () {
        Route::get('/', [\App\Http\Controllers\Customer\LoyaltyController::class, 'dashboard'])->name('customer.loyalty.index');
        Route::get('/dashboard', [\App\Http\Controllers\Customer\LoyaltyController::class, 'dashboard'])->name('customer.loyalty.dashboard');
        Route::post('/calculate-discount', [\App\Http\Controllers\Customer\LoyaltyController::class, 'calculateDiscount'])->name('customer.loyalty.calculate-discount');
        Route::get('/summary', [\App\Http\Controllers\Customer\LoyaltyController::class, 'summary'])->name('customer.loyalty.summary');
        Route::get('/transactions', [\App\Http\Controllers\Customer\LoyaltyController::class, 'transactions'])->name('customer.loyalty.transactions');
        Route::post('/max-redeemable', [\App\Http\Controllers\Customer\LoyaltyController::class, 'maxRedeemable'])->name('customer.loyalty.max-redeemable');
    });

    // Order Tracking Routes (DEPRECATED - Consolidated into shipment details)
    // Redirects for backwards compatibility
    Route::prefix('order-tracking')->group(function () {
        Route::get('/', fn() => redirect()->route('customer.shipment.myShipments'))->name('customer.tracking.index');
        Route::get('/search', fn() => redirect()->route('customer.shipment.myShipments'))->name('customer.tracking.search');
        Route::get('/{ship}', fn($ship) => redirect()->route('customer.shipment.details', $ship))->name('customer.tracking.show');
    });

    // Package Change Request Routes
    Route::prefix('package-changes')->group(function () {
        Route::get('/', [PackageChangeRequestController::class, 'index'])->name('customer.package-changes.index');
        Route::get('/package/{packageId}', [PackageChangeRequestController::class, 'getForPackage'])->name('customer.package-changes.for-package');
        Route::post('/package/{package}', [PackageChangeRequestController::class, 'store'])->name('customer.package.requestChange');
        Route::delete('/{requestId}', [PackageChangeRequestController::class, 'cancel'])->name('customer.package-changes.cancel');
    });

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('customer.notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('customer.notifications.read-all');
    });
});
