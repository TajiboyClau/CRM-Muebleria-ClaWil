<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && !$user->activo) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        abort_unless(
            $user && $user->activo && in_array($user->rol, $roles, true),
            403,
        );

        return $next($request);
    }
}
