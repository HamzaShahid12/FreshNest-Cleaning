<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Home;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'force.password' => ForcePasswordChange::class,
        ]);

        // Stripe posts to this endpoint without a CSRF token; it is protected by signature verification instead.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        $middleware->redirectGuestsTo(function (Request $request) {
            return ($request->is('staff*') || $request->is('admin*'))
                ? route('staff.login')
                : route('login');
        });

        $middleware->redirectUsersTo(fn (Request $request) => Home::pathFor($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();
            $showPage = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! app()->environment(['local', 'testing']));

            if ($showPage) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            if ($status === 419) {
                return back()->with('error', 'Your session expired. Please try again.');
            }

            return $response;
        });
    })->create();
