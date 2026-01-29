<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure customer email is verified.
 * Redirects unverified customers to the verification notice page.
 */
class EnsureCustomerEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if (!$customer) {
            return $next($request);
        }

        if ($customer instanceof MustVerifyEmail && !$customer->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::route('customer.verification.notice');
        }

        return $next($request);
    }
}
