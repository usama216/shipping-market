<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Middleware
 * 
 * Ensures only system users (admin, operator, warehouse, support, sales)
 * can access admin routes. Customers are redirected to login.
 */
class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (empty($user)) {
            return redirect()->route('login');
        }

        // Use the new isSystemUser() method for cleaner checking
        if (!$user->isSystemUser()) {
            return redirect()->route('login')
                ->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}

