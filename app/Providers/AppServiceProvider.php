<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Services\GeminiAiProvider;
use App\Support\ProviderError;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiProvider::class, function ($app) {
            return match (config('ai.provider')) {
                'gemini' => $app->make(GeminiAiProvider::class),
                default => throw new \InvalidArgumentException('Unsupported AI provider: '.config('ai.provider')),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event): void {
            Log::critical('Queue job exhausted all retry attempts.', [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue(),
                'job' => $event->job->resolveName(),
                'uuid' => $event->job->uuid(),
                'error' => ProviderError::message($event->exception),
            ]);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(600)->by($request->route()?->getName().'|'.$request->ip());
        });
    }
}
