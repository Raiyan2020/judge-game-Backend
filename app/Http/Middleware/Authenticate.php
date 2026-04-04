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
        if (in_array('admin', $this->guards)) {
            return route('admin.login');
        }
        if (in_array('club', $this->guards)) {
            return route('club.login');
        }
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
