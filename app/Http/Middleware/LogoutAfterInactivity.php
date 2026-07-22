<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogoutAfterInactivity
{
    private const IDLE_MINUTES = 15;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! $request->is('webhooks/*') && ! $request->is('up')) {
            $lastActivity = $request->session()->get('auth.last_activity');

            if ($lastActivity && now()->diffInMinutes($lastActivity) >= self::IDLE_MINUTES) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'You were signed out after 15 minutes of inactivity.');
            }

            $request->session()->put('auth.last_activity', now());
        }

        return $next($request);
    }
}
