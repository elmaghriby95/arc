<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsCurrent
{
    /** @var list<string> */
    private const ALLOWED_ROUTES = [
        'password.change',
        'password.change.store',
        'logout',
        'locale.switch',
        'branding.file',
        'assets.pdfjs',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->needsPasswordChange() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.password_change_required'),
            ], 403);
        }

        return redirect()->route('password.change');
    }
}
