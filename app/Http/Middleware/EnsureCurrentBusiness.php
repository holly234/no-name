<?php

namespace App\Http\Middleware;

use App\Services\CurrentBusinessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentBusiness
{
    public function __construct(private readonly CurrentBusinessService $currentBusinessService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $this->currentBusinessService->resolveForUser($request->user());

        if (! $business) {
            return redirect()->route('onboarding.workspace');
        }

        $request->attributes->set('currentBusiness', $business);
        View::share('currentBusiness', $business);
        View::share('userBusinesses', $request->user()->businesses()->orderBy('businesses.name')->get());

        return $next($request);
    }
}
