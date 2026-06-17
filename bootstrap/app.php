<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class, \App\Http\Middleware\HttpStatusLogger::class]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'professional.ready' => \App\Http\Middleware\EnsureProfessionalProfileReady::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileCompleted::class,
            'community.ready' => \App\Http\Middleware\EnsureCommunityAccess::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
        $exceptions->report(function (\Throwable $exception): void {
            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                if (in_array($status, [300,301,302,303,307,308,400,401,403,404,419,422,429,500,502,503,504], true) || $status >= 500) {
                    $request = request();
                    Log::warning('IRIS HTTP exception importante', [
                        'status' => $status,
                        'message' => $exception->getMessage(),
                        'method' => $request?->method(),
                        'url' => $request?->fullUrl(),
                        'user_id' => optional($request?->user())->id,
                        'ip' => $request?->ip(),
                        'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
                    ]);
                }
            }
        });
    })->create();
