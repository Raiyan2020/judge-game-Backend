<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
       
        if ($request->has('lang')) {
            app()->setLocale($request->lang);
            session()->put('lang', $request->lang);
        } elseif (session()->has('lang')) {
            app()->setLocale(session('lang'));
        }
        return $next($request);

    
    }
}
