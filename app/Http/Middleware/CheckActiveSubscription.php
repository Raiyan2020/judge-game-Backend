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
            // Diagnostic for the "rejected despite paying" report (row 57): the
            // gate logic is correct, so a wrong rejection means the account's
            // rows are not `payment_status = paid` (the paid-flip didn't run).
            // Logs the statuses so that can be confirmed on the live server.
            logger()->info('Subscription gate rejected user ' . $user->id . ' — statuses: ' . $user->subscriptions()->pluck('payment_status')->implode(','));

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