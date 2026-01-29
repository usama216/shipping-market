<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * Unified login: auto-detect if user is a customer or system user.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // First, check if this is a customer (separate table)
        $customer = Customer::where('email', $email)->first();
        if ($customer) {
            // Check if legacy customer needs password reset
            if ($customer->is_old) {
                return redirect()->route('password.request')
                    ->with('status', 'Please reset your password to continue.');
            }

            // Authenticate against customer guard
            if (!Auth::guard('customer')->attempt(['email' => $email, 'password' => $password], $remember)) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('customer.dashboard'));
        }

        // Check for legacy customer in users table (type=2) that needs migration
        $legacyCustomer = User::where('email', $email)->where('type', 2)->first();
        if ($legacyCustomer) {
            // Legacy customer - redirect to password reset to trigger migration
            return redirect()->route('password.request')
                ->with('status', 'Your account needs to be updated. Please reset your password.');
        }

        // Otherwise, authenticate as system user (admin, operator, etc.)
        $systemUser = User::where('email', $email)->first();
        if ($systemUser) {
            // Check for legacy system users that need password reset
            if ($systemUser->is_old ?? false) {
                return redirect()->route('password.request');
            }

            // Authenticate against web guard
            if (!Auth::guard('web')->attempt(['email' => $email, 'password' => $password], $remember)) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // No user found with this email
        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Destroy an authenticated session.
     * Handles logout for both customer and system user guards.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logout from whichever guard is active
        if (Auth::guard('customer')->check()) {
            Auth::guard('customer')->logout();
        } else {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

