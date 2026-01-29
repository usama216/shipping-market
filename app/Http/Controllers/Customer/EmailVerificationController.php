<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles email verification for customers.
 * Separate from the admin/operator email verification flow.
 */
class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        return $request->user('customer')->hasVerifiedEmail()
            ? redirect()->intended(route('customer.dashboard'))
            : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }

    /**
     * Handle the email verification link.
     * This route is accessible without authentication (clicked from email).
     * It validates the signed URL, finds the customer, marks as verified, and auto-logs in.
     */
    public function verify(Request $request, $id, $hash): RedirectResponse
    {
        // Find the customer by ID
        $customer = Customer::findOrFail($id);

        // Validate the hash matches the customer's email
        if (!hash_equals((string) $hash, sha1($customer->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        // If already verified, just login and redirect
        if ($customer->hasVerifiedEmail()) {
            Auth::guard('customer')->login($customer);
            return redirect()->intended(route('customer.dashboard') . '?verified=1');
        }

        // Mark email as verified
        if ($customer->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($customer));
        }

        // Auto-login the customer
        Auth::guard('customer')->login($customer);

        return redirect()->intended(route('customer.dashboard') . '?verified=1');
    }

    /**
     * Send a new email verification notification.
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user('customer')->hasVerifiedEmail()) {
            return redirect()->intended(route('customer.dashboard'));
        }

        $request->user('customer')->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

}
