<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (Throwable $e, $request) {

            if (! $request->expectsJson()) {
                return null;
            }

            $base = [
                'success' => false,
                'message' => 'Server Error',
                'data'    => [],
                'time'    => round(microtime(true) - LARAVEL_START, 3),
            ];

            // 404 (route-model binding, missing routes)
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                $base['message'] = 'Resource not found';
                return response()->json($base, 404, [], JSON_PRETTY_PRINT);
            }

            // 401 (unauthenticated)
            if ($e instanceof UnauthorizedHttpException) {
                $base['message'] = 'Unauthenticated';
                return response()->json($base, 401, [], JSON_PRETTY_PRINT);
            }

            // 403 (forbidden)
            if ($e instanceof AccessDeniedHttpException) {
                $base['message'] = 'Forbidden';
                return response()->json($base, 403, [], JSON_PRETTY_PRINT);
            }

            // 422 (validation)
            if ($e instanceof ValidationException) {
                $base['message'] = 'Validation failed';
                $base['data']    = $e->errors();
                return response()->json($base, 422, [], JSON_PRETTY_PRINT);
            }

            return null; // fallback to Laravel default (useful in debug)
        });

    })
    ->create();