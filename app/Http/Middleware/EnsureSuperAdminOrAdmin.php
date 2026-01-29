<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdminOrAdmin
{
    /**
     * Handle an incoming request.
     * Only allows super-admin or admin role to access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user || (!$user->isSuperAdmin() && $user->getRoleName() !== 'admin')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. Only super-admin or admin can access this page.'
                ], 403);
            }
            
            return Inertia::render('Admin/Errors/PermissionDenied', [
                'permission' => 'commission.manage',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
