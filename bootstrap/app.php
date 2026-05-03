<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Schedule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
          then: function () {
            //    Route::middleware('web')
            //     ->prefix('dashboard')
            //     ->name('admin.')
            //     ->group(base_path('routes/admin.php'));

             

        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setLocale' => \App\Http\Middleware\SetLocale::class,
            'localization' => \App\Http\Middleware\Localization::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            


        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new \App\Jobs\CloseExpiredExecutionCases)->daily();
        $schedule->job(new \App\Jobs\ProcessExpiredPolls)->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return \responder::error(\Arr::first(\Arr::first($e->errors())));
            }

        });
        //
    })->create();

