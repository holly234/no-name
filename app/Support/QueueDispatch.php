<?php

namespace App\Support;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Testing\Fakes\BusFake;

final class QueueDispatch
{
    public static function dispatch(object $job): mixed
    {
        if (self::shouldRunInline()) {
            return Bus::dispatchSync($job);
        }

        Bus::dispatch($job);

        return null;
    }

    public static function shouldRunInline(): bool
    {
        if (Bus::getFacadeRoot() instanceof BusFake) {
            return false;
        }

        return config('queue.default') === 'sync'
            || app()->runningUnitTests()
            || ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) === 'testing'
            || str_contains(str_replace('\\', '/', (string) ($_SERVER['argv'][0] ?? $_SERVER['PHP_SELF'] ?? '')), 'phpunit')
            || class_exists(\PHPUnit\Framework\TestCase::class);
    }
}
