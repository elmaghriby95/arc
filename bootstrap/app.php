<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $detail = __('validation.post_too_large_detail', [
                'content' => $contentLength > 0 ? round($contentLength / 1048576, 1).' MB' : '—',
                'post_max' => (string) ini_get('post_max_size'),
                'upload_max' => (string) ini_get('upload_max_filesize'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $detail,
                    'errors' => ['files' => [$detail]],
                    'code' => 'post_too_large',
                    'limits' => [
                        'content_length' => $contentLength,
                        'post_max_size' => ini_get('post_max_size'),
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                    ],
                ], 413);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['files' => $detail])
                ->with('error', $detail);
        });
    })->create();
