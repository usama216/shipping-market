<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedirectIfAuthenticated Middleware
 * 
 * Redirects authenticated users away from guest pages (login, register)
 * to their appropriate dashboard based on guard type.
 */
class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? ['customer', 'web'] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Redirect based on which guard is authenticated
                return $guard === 'customer'
                    ? redirect(RouteServiceProvider::CUSTOMER_HOME)
                    : redirect(RouteServiceProvider::ADMIN_HOME);
            }
        }

        return $next($request);
    }
}

