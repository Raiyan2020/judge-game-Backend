<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected $guards;


    public function handle($request, Closure $next, ...$guards)
    {
        $this->guards = $guards;

        return parent::handle($request, $next, ...$guards);
    }

    protected function redirectTo($request)
    {
        // API clients must receive an authentication response, not a redirect
        // to a web login route that does not exist for the default guard.
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        if (in_array('admin', $this->guards)) {
            return route('admin.login');
        }
        if (in_array('club', $this->guards)) {
            return route('club.login');
        }

        return null;
    }
}
