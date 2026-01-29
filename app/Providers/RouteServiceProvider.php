<?php

namespace App\Providers;

use App\Models\CustomerAddress;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Route;

class RouteServiceProvider extends ServiceProvider
{
    public const ADMIN_HOME = '/dashboard';

    public const CUSTOMER_HOME = '/customer/dashboard';

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Route model binding
        Route::model('address', CustomerAddress::class);

        Route::middleware('web')
            ->group(base_path('routes/auth.php'))
            ->group(base_path('routes/customer.php'))
            ->group(base_path('routes/web.php'));
    }
}
