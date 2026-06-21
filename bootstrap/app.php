<?php

use App\Console\Commands\SyncQcToKhoCommand;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        SyncQcToKhoCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
              Request::HEADER_X_FORWARDED_HOST |
              Request::HEADER_X_FORWARDED_PORT |
              Request::HEADER_X_FORWARDED_PROTO |
              Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
