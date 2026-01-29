<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customer Middleware
 * 
 * Ensures only authenticated customers (from customers table)
 * can access customer routes. System users are redirected to login.
 */
class Customer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use customer guard directly
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login')
                ->with('error', 'Please login as a customer to access this area.');
        }

        return $next($request);
    }
}

