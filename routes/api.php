<?php

use App\Http\Controllers\N8nController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:webhooks')->group(function () {
    Route::get('/webhooks/meta', [WebhookController::class, 'verifyMeta'])->name('webhooks.meta.verify');
    Route::post('/webhooks/meta', [WebhookController::class, 'meta'])->name('webhooks.meta');
    Route::post('/webhooks/gmail/pubsub', [WebhookController::class, 'gmailPubSub'])->name('webhooks.gmail.pubsub');
    Route::post('/webhooks/telegram/{account}', [WebhookController::class, 'telegram'])->name('webhooks.telegram');
});

Route::middleware('throttle:api')->group(function () {
    if (config('services.n8n.compatibility_endpoints_enabled')) {
        Route::post('/n8n/incoming-message', [N8nController::class, 'incomingMessage']);
        Route::post('/n8n/save-outgoing-message', [N8nController::class, 'saveOutgoingMessage']);
        Route::post('/n8n/log-event', [N8nController::class, 'logEvent']);
    }

    if (config('services.webhooks.legacy_endpoints_enabled')) {
        Route::post('/incoming-message', [WebhookController::class, 'incomingMessage']);
        Route::post('/generate-ai-reply', [WebhookController::class, 'generateAiReply']);
        Route::post('/save-outgoing-message', [WebhookController::class, 'saveOutgoingMessage']);
    }
});
