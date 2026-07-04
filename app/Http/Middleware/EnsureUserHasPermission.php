<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء.');
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء.');
    }
}
