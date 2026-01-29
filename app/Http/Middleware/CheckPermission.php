<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPermission Middleware
 * 
 * Checks if the authenticated user has the required permission.
 * Super admins bypass all permission checks.
 * 
 * Usage in routes:
 *   Route::get('/packages', ...)->middleware('permission:packages.view');
 *   Route::post('/packages', ...)->middleware('permission:packages.create');
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission The required permission name
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Super admins have all permissions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if (!$user->hasPermissionTo($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You do not have permission to perform this action.',
                    'required_permission' => $permission,
                ], 403);
            }

            // Render permission denied page with helpful info
            return \Inertia\Inertia::render('Admin/Errors/PermissionDenied', [
                'permission' => $permission,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
