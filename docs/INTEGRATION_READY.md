# Integration Readiness

This app is ready to continue wiring real OpenAI, Meta, and n8n integrations. Gmail and Telegram are now the first real provider connections: Gmail OAuth, manual/scheduled inbox sync, Gmail text replies, Telegram webhooks, and Telegram replies/media are implemented, while Meta and AI behavior remain demo/fake or placeholder.

## Shared Rules

- Keep every business-owned operation scoped by `business_id`.
- Never expose connected account `access_token` in Blade or JSON responses.
- Store provider tokens only in connected account token columns; `access_token` and `refresh_token` are encrypted casts.
- Support multiple connected accounts for the same platform inside one workspace.
- Route inbound provider messages by account identifiers, not just by platform.
- Public inbound APIs must require a valid webhook secret.
- Queue provider calls once they become slow or unreliable.

## Environment Variables

```env
APP_WEBHOOK_SECRET=

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
OPENAI_TIMEOUT=30

META_APP_ID=
META_APP_SECRET=
META_VERIFY_TOKEN=
META_WEBHOOK_SECRET=
META_WEBHOOK_VERIFY_TOKEN=
META_GRAPH_VERSION=v25.0
META_EMBEDDED_SIGNUP_CONFIG_ID=
META_DEVELOPMENT_CONNECT_ENABLED=false
LEGAL_CONTACT_EMAIL=perpetualdev2@gmail.com
META_REDIRECT_URI="${APP_URL}/dashboard/accounts/meta/callback"

GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REDIRECT_URI="${APP_URL}/dashboard/accounts/gmail/callback"
GMAIL_PUBSUB_TOPIC=
GMAIL_PUBSUB_VERIFICATION_TOKEN=

N8N_BASE_URL=
N8N_WEBHOOK_SECRET=
```

## AI Provider and Credits

Current implementation:

- `App\Contracts\AiProvider` keeps conversation logic provider-neutral.
- `GeminiAiProvider` calls Gemini 2.5 Flash-Lite with structured JSON output and records tokens, latency, actual/estimated provider cost, state, confidence, intent, and escalation reason.
- `AiPromptBuilder` supplies bounded recent history plus workspace FAQs, products/services, business rules, assistant tone, forbidden claims, and handover instructions.
- `ProcessAiReply` runs on the `ai` queue only when `AI_ENABLED=true`.
- The job reserves workspace credits before provider work, settles against actual token usage, releases failed reservations, and routes empty-credit/provider/delivery failures safely to staff.
- `OutboundChannelService` delivers AI text through real Telegram and Meta accounts; demo/non-provider conversations remain local. Gmail automatic AI replies remain intentionally disabled.
- Operators can grant beta credits with `php artisan ai:credits:grant {business-id} {credits} --reference=...` until verified checkout exists.

Required environment:

```env
AI_ENABLED=false
AI_PROVIDER=gemini
AI_RESERVATION_CREDITS=25
AI_TOKENS_PER_CREDIT=100
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash-lite
GEMINI_TIMEOUT=30
GEMINI_BILLING_MODE=free
```

Keep `AI_ENABLED=false` until the key is configured, the `ai` worker is running, and test credits are granted. Gemini's free tier may use submitted content to improve Google products; use paid billing/data terms before processing real customer conversations.

## Legacy AI Seam Notes

WhatsApp Cloud API foundation is implemented through Meta Embedded Signup. The Accounts page opens Meta’s hosted onboarding, receives a one-time code, and completes the server-side token exchange and WABA subscription. Incoming WhatsApp text messages are accepted through the verified Meta webhook and staff text replies are sent through the WhatsApp Cloud API. Configure `META_APP_ID`, `META_APP_SECRET`, and `META_EMBEDDED_SIGNUP_CONFIG_ID`; do not collect access tokens from users.

Current seam:

- `app/Services/AiReplyService.php`
- `app/Services/MessageIngestionService.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Controllers/N8nController.php`

Current behavior:

- `AiReplyService::decideState()` uses keyword/confidence demo logic.
- `AiReplyService::generatePlaceholderReply()` returns a static business-aware reply.

Previously planned steps (superseded by the provider-neutral Gemini implementation above):

1. Replace `generatePlaceholderReply()` with an OpenAI-backed response method.
2. Keep `decideState()` deterministic enough to preserve the four-state contract.
3. Feed business context from `AiSetting`, FAQs, products, and business rules.
4. Return `Needs Human` for low confidence, refund/discount/complaint/custom quote/approval requests, and missing knowledge.
5. Add request/response logging without storing sensitive customer content unnecessarily.
6. Add retry/timeout handling and move calls to queued jobs before production traffic.

Legacy OpenAI config seam retained for a possible fallback provider:

```php
config('services.openai.api_key')
config('services.openai.model')
config('services.openai.timeout')
```

## Gmail

Current seam:

- `app/Services/GmailConnectionService.php`
- `app/Http/Controllers/ConnectedAccountController.php`
- `resources/views/dashboard/accounts.blade.php`
- `connected_accounts`, `conversations`, `messages`, and `customers` tables

Current behavior:

- Gmail OAuth connect is implemented at `GET /dashboard/accounts/gmail/redirect` and `GET /dashboard/accounts/gmail/callback`.
- Connected Gmail accounts are stored with `platform=gmail`.
- Access and refresh tokens are stored encrypted on `connected_accounts`.
- When `GMAIL_PUBSUB_TOPIC` is configured, Gmail connect attempts to register `users/me/watch` for the account inbox.
- Google Pub/Sub should push to `POST /api/webhooks/gmail/pubsub?token={GMAIL_PUBSUB_VERIFICATION_TOKEN}`.
- A valid Pub/Sub notification dispatches `ProcessGmailPubSubNotification` to the `sync` queue; its handler triggers the Gmail inbox sync path for the matching connected account.
- `POST /dashboard/accounts/gmail/{account}/sync` manually imports the latest 20 inbox emails.
- The manual dashboard sync stays synchronous so the user receives immediate imported/skipped feedback; automatic operation does not require pressing this button.
- `php artisan gmail:sync` runs through the Laravel scheduler and dispatches one `SyncGmailAccount` job per connected Gmail account to the `sync` queue.
- `php artisan gmail:renew-watch` runs daily through the Laravel scheduler and renews connected Gmail Pub/Sub watches before expiry.
- Production must call `php artisan schedule:run` every minute; this drives both polling fallback and automatic watch renewal.
- Imported Gmail messages become `Gmail` channel conversations/messages inside the existing unified inbox.
- Duplicate Gmail messages are skipped using `metadata.gmail_message_id`.
- Gmail conversations default to `Needs Human` and `ai_mode=human` so no email is auto-sent accidentally.
- Staff text replies to Gmail conversations are sent through the Gmail API with reply-thread metadata when available.

Google Cloud setup:

1. Create a project in Google Cloud Console.
2. Enable Gmail API.
3. Configure OAuth consent screen.
4. Create OAuth Client ID for Web Application.
5. Add redirect URI: `{APP_URL}/dashboard/accounts/gmail/callback`.
6. Add `GMAIL_CLIENT_ID`, `GMAIL_CLIENT_SECRET`, and `GMAIL_REDIRECT_URI` to `.env`.
7. For push sync, create a Pub/Sub topic and set `GMAIL_PUBSUB_TOPIC=projects/{project-id}/topics/{topic-name}`.
8. Grant Gmail publish permission to the topic service account: `gmail-api-push@system.gserviceaccount.com`.
9. Create a Pub/Sub push subscription pointing to `{APP_URL}/api/webhooks/gmail/pubsub?token={GMAIL_PUBSUB_VERIFICATION_TOKEN}`.
10. Reconnect Gmail accounts or call Gmail watch registration so Google starts sending push notifications.

Current Gmail limitations:

- Pub/Sub push is implemented as push-triggered sync, not direct Gmail history diff replay yet.
- Gmail `watch` expires and must be renewed periodically before expiration.
- Scheduler polling should remain enabled as a safety fallback.
- Gmail attachment sending from the inbox is not implemented yet.
- A running `sync` queue worker is required for scheduled and Pub/Sub-triggered Gmail imports.

## Meta

Scope note:

- This product should ingest private inbox messages only.
- Do not ingest comments, post activity, story replies, or public feed interactions unless the product scope changes later.

Current seam:

- `app/Services/MetaConnectionService.php`
- `app/Http/Controllers/ConnectedAccountController.php`
- `app/Http/Controllers/WebhookController.php`
- `routes/api.php`
- `connected_accounts` table

Current behavior:

- Account connection is fake/demo through `dashboard.accounts.fake-connect`.
- WhatsApp production/customer onboarding uses Meta Embedded Signup and stores the returned token encrypted per workspace.
- With `META_DEVELOPMENT_CONNECT_ENABLED=true`, an owner/admin-only form validates and connects test WhatsApp phone numbers, Facebook Pages, and Instagram professional accounts owned by app-role users.
- Development tokens are encrypted, never rendered back, and may subscribe their provider asset to the shared signed Meta webhook.
- Signed WhatsApp, Facebook Messenger, and Instagram message webhooks route by provider asset ID to the correct workspace.
- Staff text replies use WhatsApp Cloud API, Messenger Send API, or Instagram Send API according to the conversation channel.
- Keep the development connector disabled during normal production use after public OAuth approval.
- Demo connection creates a new connected account row; it does not overwrite existing accounts for the same platform.
- Connected accounts can be disconnected. Disconnect clears the token and hides the account from the active Accounts UI while preserving the row for audit/history.
- Incoming Meta-compatible messages can enter through `POST /api/webhooks/meta`.
- Tokens can be stored encrypted in `connected_accounts.access_token`.

Useful connected account columns:

- `platform`
- `account_name`
- `external_account_id`
- `page_id`
- `phone_number_id`
- `access_token`
- `refresh_token`
- `token_expires_at`
- `provider_meta`
- `status`
- `connected_at`

Next implementation steps:

1. Implement public Facebook and Instagram OAuth/connect flows in `MetaConnectionService` after Meta grants the required advanced permissions.
2. Store page/account identifiers in `page_id`, `phone_number_id`, and `external_account_id`.
3. Store page/account access token in encrypted `access_token`.
4. Implement Meta webhook verification using `META_VERIFY_TOKEN`.
5. Normalize webhook payloads into the `MessageIngestionService::ingest()` payload shape:

```php
[
    'business_id' => $businessId,
    'channel' => 'Instagram'|'Facebook'|'WhatsApp',
    'external_account_id' => $providerAccountId,
    'page_id' => $facebookOrInstagramPageId,
    'phone_number_id' => $whatsAppPhoneNumberId,
    'customer_name' => $name,
    'customer_external_id' => $platformScopedCustomerId,
    'body' => $messageText,
    'confidence' => $confidence,
]
```

Customer identity note:

- The staff UI currently displays `customer_external_id` as a friendly identity when available: Instagram/Facebook username or WhatsApp number.
- Real Meta integration should still preserve a stable provider-scoped customer identifier for matching. If Meta payloads provide both a stable ID and a friendly username/phone, add a separate display field before production instead of overloading one column for both jobs.

6. For outbound replies, use `ConversationMessageService` for local persistence first, then call Meta send APIs.

Recommended config source:

```php
config('services.meta.app_id')
config('services.meta.app_secret')
config('services.meta.verify_token')
config('services.meta.graph_version')
config('services.meta.redirect_uri')
```

## n8n

Current seam:

- `routes/api.php`
- `app/Http/Controllers/N8nController.php`
- `app/Services/MessageIngestionService.php`
- `app/Services/ConversationMessageService.php`

Current routes:

- `POST /api/n8n/incoming-message`
- `POST /api/n8n/save-outgoing-message`
- `POST /api/n8n/log-event`

Security:

- n8n requests must include:

```http
X-N8N-SECRET: your-secret
```

- The app compares the header with `N8N_WEBHOOK_SECRET`, falling back to `APP_WEBHOOK_SECRET`.

Expected incoming-message payload:

```json
{
  "business_id": 1,
  "channel": "Instagram",
  "external_account_id": "instagram-account-or-page-id",
  "customer_name": "Kemi Adebayo",
  "customer_external_id": "@kemiadebayo",
  "body": "Can I book tomorrow?",
  "confidence": 0.82
}
```

Expected save-outgoing-message payload:

```json
{
  "conversation_id": 1,
  "body": "Thanks, we will follow up.",
  "sender_type": "system"
}
```

Expected log-event payload:

```json
{
  "business_id": 1,
  "event_type": "provider_event",
  "status": "success",
  "message": "Workflow completed."
}
```

Next implementation steps:

1. Create n8n workflow using the routes above.
2. Keep Laravel as source of truth for conversations/messages.
3. Use n8n only for provider automation and orchestration.
4. Log provider failures through `/api/n8n/log-event`.
5. Keep retries/idempotency in mind before production.

## Current Verification

Last known green checks:

```bash
npm run build
.\vendor\bin\phpunit.bat --do-not-cache-result
php artisan view:cache
```

Expected suite size after the latest readiness work: `55 tests, 171 assertions`.
