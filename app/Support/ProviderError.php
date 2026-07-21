<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class ProviderError
{
    public static function message(Throwable|string $error): string
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        $patterns = [
            '#(https://api\.telegram\.org/bot)[^/\s]+#i' => '$1[REDACTED]',
            '/(authorization\s*[:=]\s*bearer\s+)[^\s,}\]]+/i' => '$1[REDACTED]',
            '/([?&](?:key|api_key|token|access_token|refresh_token)=)[^&\s]+/i' => '$1[REDACTED]',
            '/("(?:access_token|refresh_token|api_key|client_secret|password)"\s*:\s*")[^"]+/i' => '$1[REDACTED]',
            '/((?:access_token|refresh_token|api_key|client_secret|password)\s*[:=]\s*)[^\s,}\]]+/i' => '$1[REDACTED]',
        ];

        return str($message)
            ->replaceMatches(array_keys($patterns), array_values($patterns))
            ->limit(1000)
            ->toString();
    }

    public static function report(Throwable $exception, array $context = []): void
    {
        Log::error('External provider operation failed.', $context + [
            'exception' => $exception::class,
            'message' => self::message($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
