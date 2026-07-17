<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWorkspaceRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $business = $request->attributes->get('currentBusiness');
        $role = $request->user()?->workspaceRole($business);

        abort_unless($role && in_array($role, array_map('strtolower', $roles), true), 403);

        return $next($request);
    }
}
