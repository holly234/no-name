<?php

use App\Http\Controllers\N8nController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::post('/n8n/incoming-message', [N8nController::class, 'incomingMessage']);
    Route::post('/n8n/save-outgoing-message', [N8nController::class, 'saveOutgoingMessage']);
    Route::post('/n8n/log-event', [N8nController::class, 'logEvent']);

    Route::get('/webhooks/meta', [WebhookController::class, 'verifyMeta']);
    Route::post('/webhooks/meta', [WebhookController::class, 'meta']);
    Route::post('/webhooks/gmail/pubsub', [WebhookController::class, 'gmailPubSub']);
    Route::post('/webhooks/telegram/{account}', [WebhookController::class, 'telegram']);
    Route::post('/incoming-message', [WebhookController::class, 'incomingMessage']);
    Route::post('/generate-ai-reply', [WebhookController::class, 'generateAiReply']);
    Route::post('/save-outgoing-message', [WebhookController::class, 'saveOutgoingMessage']);
});
