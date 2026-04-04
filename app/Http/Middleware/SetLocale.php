<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
class SetLocale
{
    
     private $locales = [ 'en','ar'];
     public function handle($request, Closure $next)
     {
         $locale = $request->header('Accept-Language');
         if (in_array($locale, $this->locales)) {
             App::setLocale($locale);
         } else {
             App::setLocale('en');
         }
 
         return $next($request);
     }
}
