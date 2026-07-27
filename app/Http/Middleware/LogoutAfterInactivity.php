<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogoutAfterInactivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! $request->is('webhooks/*') && ! $request->is('up')) {
            $lastActivity = $request->session()->get('auth.last_activity');
            $idleMinutes = (int) config('session.idle_timeout', 10);

            if ($lastActivity && now()->diffInSeconds($lastActivity, true) >= $idleMinutes * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', "You were signed out after {$idleMinutes} minutes of inactivity.");
            }

            if (! $request->routeIs('dashboard.inbox.pulse')) {
                $request->session()->put('auth.last_activity', now());
            }
        }

        return $next($request);
    }
}
