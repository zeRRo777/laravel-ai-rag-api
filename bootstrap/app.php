<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $renderForbiddenResponse = function (AccessDeniedHttpException|AuthorizationException $e, Request $request): JsonResponse {
            return response()->json([
                'type'     => config('app.url') . '/errors/forbidden',
                'title'    => 'You not authorized',
                'status'   => 403,
                'detail'   => 'Доступ к ресурсу запрещен!',
                'instance' => $request->getUri(),
            ], 403);
        };

        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e, Request $request) use ($renderForbiddenResponse) {
            if ($request->is('api/*')) {
                return $renderForbiddenResponse($e, $request);
            }

            abort($e->getCode(), $e->getMessage());
        });
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'type'   => config('app.url') . '/errors/validation-error',
                    'title'  => 'Validation Error',
                    'status' => 422,
                    'detail' => 'Произошла одна или несколько ошибок проверки.',
                    'instance' => $request->getUri(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'type'   => config('app.url') . '/errors/unauthorized',
                    'title'  => 'You not authorized',
                    'status' => 401,
                    'detail' => 'Доступ к ресурсу доступен только авторизованным пользователям!',
                    'instance' => $request->getUri(),
                ], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'type' => config('app.url') . '/errors/not-found',
                    'title' => 'Not Found',
                    'status' => 404,
                    'detail' => $e->getMessage(),
                    'instance' => $request->getUri(),
                ], 404);
            }
        });
    })->create();
