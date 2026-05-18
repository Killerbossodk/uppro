<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Redirection personnalisée pour les requêtes API non authentifiées
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Non authentifié'], 401);
            }
            // Si vous avez une route de connexion web, vous pouvez la spécifier
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();