<?php

namespace Tests\Unit;

use App\Support\ProviderError;
use PHPUnit\Framework\TestCase;

class ProviderErrorTest extends TestCase
{
    public function test_it_redacts_provider_credentials(): void
    {
        $message = ProviderError::message(
            'POST https://api.telegram.org/bot123456:ABC-secret/sendMessage?access_token=secret '
            .'Authorization: Bearer another-secret "refresh_token":"refresh-secret"'
        );

        $this->assertStringNotContainsString('123456:ABC-secret', $message);
        $this->assertStringNotContainsString('another-secret', $message);
        $this->assertStringNotContainsString('refresh-secret', $message);
        $this->assertStringContainsString('[REDACTED]', $message);
    }
}
