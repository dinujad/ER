<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\EnforceActionPermission::class]);
        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\EnforceActionPermission::class
        );
        $middleware->alias([
            'sales_person'    => \App\Http\Middleware\CheckSalesPerson::class,
            'driver'          => \App\Http\Middleware\CheckDriver::class,
            'supervisor'      => \App\Http\Middleware\CheckSupervisor::class,
            'accounting.session' => \App\Http\Middleware\AccountingSessionDefaults::class,
            'accounting.access' => \App\Http\Middleware\AccountingAccess::class,
            'permission'      => \App\Http\Middleware\CheckPermission::class,
            'erp.permission'  => \App\Http\Middleware\EnforceRoutePermission::class,
        ]);
    })
    ->withBindings([
        // Use Laravel's built-in CORS when something requests the old Fruitcake class.
        'Fruitcake\Cors\HandleCors' => \Illuminate\Http\Middleware\HandleCors::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
