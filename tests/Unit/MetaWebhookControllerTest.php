<?php

namespace Tests\Unit;

use Tests\TestCase;

class MetaWebhookControllerTest extends TestCase
{
    public function test_meta_webhook_verification_returns_raw_challenge_when_token_matches(): void
    {
        config([
            'services.meta_webhook.verify_token' => 'adviet_meta_webhook_2026',
        ]);

        $response = $this->get('/webhooks/meta?hub.mode=subscribe&hub.verify_token=adviet_meta_webhook_2026&hub.challenge=123456');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame('123456', $response->getContent());
    }

    public function test_meta_webhook_verification_rejects_invalid_token(): void
    {
        config([
            'services.meta_webhook.verify_token' => 'adviet_meta_webhook_2026',
        ]);

        $response = $this->get('/webhooks/meta?hub.mode=subscribe&hub.verify_token=sai-token&hub.challenge=123456');

        $response->assertForbidden();
        $response->assertJson([
            'error' => 'Verification failed',
        ]);
    }
}
