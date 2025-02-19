<?php

namespace App\Http\Middleware;

use App\Enums\ROLE;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = Auth::user();

        if ($user->hasRole(ROLE::ADMIN)) {
            return $next($request); // Admins bypass role checks
        }

        if (! $user->hasRole(ROLE::ORGANIZER)) {
            return response()->json(['success' => false, 'errors' => [__('common.permission_denied')]]);
        }

        return $next($request);
    }
}
