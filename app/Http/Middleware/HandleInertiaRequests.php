<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Session;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * Handle an incoming request.
     * Skip Inertia for website routes (Blade views).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip Inertia for website routes (Blade views)
        if ($request->routeIs('web.*')) {
            return $next($request);
        }
        
        return parent::handle($request, $next);
    }
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Determine if the request should be handled by Inertia.
     * Skip Inertia for file downloads and binary responses.
     */
    public function rootView(Request $request): string
    {
        // Skip Inertia for barcode routes (PDF/ZPL downloads and views)
        if ($request->routeIs('*.barcode.*')) {
            return $this->rootView;
        }
        
        // Skip Inertia for website routes (Blade views)
        if ($request->routeIs('web.*')) {
            return $this->rootView;
        }

        return parent::rootView($request);
    }


    /**
     * Define the props that are shared by default.
     * Handles both customer and system user guards.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = null;
        $userType = null;
        $permissions = [];
        $isSuperAdmin = false;

        // Check customer guard first (more common for this platform)
        if (Auth::guard('customer')->check()) {
            $user = Auth::guard('customer')->user();
            $user->load('defaultAddresses', 'warehouse');
            $userType = 'customer';
        }
        // Then check system user guard
        elseif (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $user->load('defaultAddresses');
            $userType = 'system';

            // Check if user is a super-admin (system user with super-admin role)
            $isSuperAdmin = $user->isSuperAdmin();

            // Get all permission names for the user (for non-super-admins)
            if (!$isSuperAdmin && method_exists($user, 'getAllPermissions')) {
                $permissions = $user->getAllPermissions()->pluck('name')->toArray();
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'userType' => $userType,
                'permissions' => $permissions,
                'isSuperAdmin' => $isSuperAdmin,
            ],
            'notifications' => $user ? $this->getNotifications($user) : [],
            'alert' => Session::get('alert'),
            'flash' => [
                'success' => Session::get('success'),
                'error' => Session::get('error'),
                'message' => Session::get('message'),
            ],
        ];
    }

    /**
     * Get unread notifications for the authenticated user.
     * 
     * Wrapped in try-catch to prevent 500 errors if notifications table
     * has issues (e.g., missing table, schema problems)
     * 
     * @param mixed $user The authenticated user (Customer or User)
     * @return array
     */
    private function getNotifications($user): array
    {
        try {
            if (!method_exists($user, 'unreadNotifications')) {
                return [];
            }

            return $user->unreadNotifications()
                ->take(10)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->data['type'] ?? 'general',
                        'message' => $notification->data['message'] ?? 'New notification',
                        'data' => $notification->data,
                        'created_at' => $notification->created_at->diffForHumans(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Log the error but don't crash the request
            \Log::warning('Failed to load notifications', [
                'user_id' => $user->id ?? null,
                'user_type' => get_class($user),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

