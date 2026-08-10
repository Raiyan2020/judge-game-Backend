<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckActiveSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        $activeSubscription = $user->activeSubscription()->with('package')->first();

        if (!$activeSubscription) {
            // HTTP 402 Payment Required — a distinct status (not a generic 422)
            // so the app can tell "you need a subscription" from ordinary input
            // errors and route the user to the packages screen. `error_code`
            // gives a stable machine key alongside the localized `msg`.
            return response()->json([
                'status' => false,
                'msg' => __('You do not have an active subscription. Please subscribe to a package first'),
                'error_code' => 'no_active_subscription',
            ], 402);
        }

        return $next($request);
    }
}