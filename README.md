# Perpetual Inbox AI

Perpetual Inbox AI is a Laravel/Blade SaaS MVP for managing Instagram, Facebook, WhatsApp, Gmail, and Telegram-style customer conversations from one organized inbox.

The channel scope is private inbox messages only. Comments, posts, public feed activity, and story replies are intentionally out of scope for now.

The current product direction is calm customer communication and workflow management, not AI hype. AI/smart assistance exists in the background through the conversation state engine, while the main product value is unified inbox, human takeover, team visibility, customer history, and faster replies.

## Current Status

Built and working as a local MVP:

- Laravel 12, Blade, Tailwind CSS, Alpine.js, Vite
- Email/password auth via Laravel Breeze scaffold
- Google OAuth routes/config placeholders via Socialite
- Multi-tenant business/workspace model
- Session-backed active workspace resolution
- Business-scoped dashboard data
- Dashboard defaults to inbox
- Dark, flat, WhatsApp/Instagram-inspired inbox UI with no dashboard gradients
- Lightweight SPA-style dashboard navigation for internal dashboard links/forms, including pending feedback and double-submit protection
- Demo connected accounts for Instagram, Facebook, and WhatsApp
- Real Gmail OAuth connection with encrypted token storage and manual on-demand email sync
- Real Telegram bot-backed connection with encrypted bot token storage, webhook registration, incoming message/media ingestion, and text/media replies through the Telegram Bot API
- Multiple connected accounts per social platform per workspace
- Disconnect flow for connected accounts; disconnected records are preserved internally but hidden from the active Accounts UI
- AI Conversation State Engine demo workflow
- Manual replies, behavior-backed human takeover switch, resume automation, and close support in backend
- AI settings toggles are wired into behavior for auto replies, human takeover, and business-hours reply gating
- Editable knowledge base with mobile-friendly section tabs for FAQs, products/services, business rules, and saved replies
- Secured webhook/API write routes
- Per-user conversation read tracking
- Conversation/message query limits and inbox indexes for scale
- Feature tests covering workspace isolation, inbox filtering/unread, webhooks, connected account lifecycle, and authorization
- Polished UI interactions may use focused third-party libraries when they provide better quality than hand-rolled controls. Current example: `wavesurfer.js` powers inbox voice-note waveform recording and playback.

Latest verification:

```bash
npm run build
.\vendor\bin\phpunit.bat --do-not-cache-result
php artisan view:cache
```

Last known passing test count: `74 tests, 292 assertions`.

## Core Product

Every conversation belongs to one state:

- `AI Handling`
- `Waiting`
- `Needs Human`
- `Closed`

The inbox lets staff see:

- which conversations need staff attention
- which conversations are waiting for customer response
- which conversations are being handled automatically
- which conversations are closed
- which social channel each conversation came from

The UI includes:

- social-channel filter row: All, Instagram, WhatsApp, Facebook, Gmail, Telegram
- state filter row: All, Needs Human, AI Handling, Waiting, Closed
- full-width DM-style conversation list
- mobile list-to-thread behavior
- thread view with message composer
- manual composer supports text, private file/image/audio/video attachments, attachment-only staff replies, and in-app voice-note recording
- customer context panel on wide desktop
- mobile customer profile bottom sheet opened from the chat header name
- channel-aware customer identity labels: Instagram username, Facebook username, WhatsApp number, Gmail email address, or Telegram chat ID
- human takeover switch
- top-right toast notifications that auto-dismiss after 3 seconds
- conversations default to `ai_mode=auto`; `Needs Human` is a status, while `human` mode is reserved for explicit takeover or disabled automation conditions

Design reference:

- [Messaging SaaS Visual Mood Board](docs/VISUAL_MOOD_BOARD.md)

## Main Routes

Public/auth:

- `GET /`
- `GET /login`
- `GET /register`
- `GET /forgot-password`
- `GET /auth/google/redirect`
- `GET /auth/google/callback`

Workspace:

- `GET /onboarding/workspace`
- `POST /onboarding/workspace`
- `POST /workspace/switch`

Dashboard:

- `GET /dashboard` redirects to `/dashboard/inbox`
- `GET /dashboard/inbox`
- `POST /dashboard/inbox/{conversation}/reply`
- `POST /dashboard/inbox/{conversation}/take-over`
- `POST /dashboard/inbox/{conversation}/resume-ai`
- `POST /dashboard/inbox/{conversation}/close`
- `GET /dashboard/accounts`
- `POST /dashboard/accounts/fake-connect`
- `GET /dashboard/accounts/gmail/redirect`
- `GET /dashboard/accounts/gmail/callback`
- `POST /dashboard/accounts/gmail/{account}/sync`
- `POST /dashboard/accounts/telegram/connect`
- `PATCH /dashboard/accounts/{account}/disconnect`
- `GET /dashboard/ai-settings`
- `PATCH /dashboard/ai-settings`
- `GET /dashboard/knowledge-base`
- `POST /dashboard/knowledge-base/faqs`
- `PATCH /dashboard/knowledge-base/faqs/{faq}`
- `DELETE /dashboard/knowledge-base/faqs/{faq}`
- `POST /dashboard/knowledge-base/products`
- `PATCH /dashboard/knowledge-base/products/{product}`
- `DELETE /dashboard/knowledge-base/products/{product}`
- `POST /dashboard/knowledge-base/rules`
- `PATCH /dashboard/knowledge-base/rules/{rule}`
- `DELETE /dashboard/knowledge-base/rules/{rule}`
- `POST /dashboard/knowledge-base/saved-replies`
- `PATCH /dashboard/knowledge-base/saved-replies/{savedReply}`
- `DELETE /dashboard/knowledge-base/saved-replies/{savedReply}`
- `GET /dashboard/settings`
- `PATCH /dashboard/settings/business`

API:

- `POST /api/n8n/incoming-message`
- `POST /api/n8n/save-outgoing-message`
- `POST /api/n8n/log-event`
- `POST /api/webhooks/meta`
- `POST /api/webhooks/telegram/{account}`
- `POST /api/incoming-message`
- `POST /api/generate-ai-reply`
- `POST /api/save-outgoing-message`

## Security Notes

Business-owned dashboard data must always be scoped by `business_id`.

Implemented guardrails:

- current business resolved through `CurrentBusinessService`
- `current.business` middleware shares active workspace context
- workspace switching only allows businesses attached to the user
- inbox mutation actions reject foreign-business conversations
- connected account access tokens are encrypted
- connected accounts are scoped to the active business before disconnecting
- disconnected connected-account records are kept for audit/history but hidden from the active Accounts page
- public webhook writes require a valid secret
- n8n endpoints require `X-N8N-SECRET`
- `throttle:api` rate limiter is registered
- per-business webhook secrets are supported

Webhook secret behavior:

- New workspaces get a generated `webhook_secret` beginning with `whsec_`.
- Public webhook routes prefer the business-specific secret when the request includes a business or conversation.
- `APP_WEBHOOK_SECRET` / `META_WEBHOOK_SECRET` remain global fallbacks.

Headers:

```http
X-WEBHOOK-SECRET: whsec_or_global_secret
X-META-SECRET: whsec_or_global_secret
X-N8N-SECRET: n8n_or_app_secret
```

## Important Environment Variables

```env
APP_NAME="Perpetual Inbox AI"
APP_WEBHOOK_SECRET=
META_WEBHOOK_SECRET=
N8N_WEBHOOK_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REDIRECT_URI="${APP_URL}/dashboard/accounts/gmail/callback"
TELEGRAM_API_BASE=https://api.telegram.org/bot
OPENAI_API_KEY=
```

Real Meta/OpenAI integrations are not implemented yet. Gmail is implemented for OAuth connection and manual inbox sync; Gmail sending and Pub/Sub push are not enabled yet. Telegram is implemented through the Bot API, not private-user MTProto sessions. For real-time Telegram messages, `APP_URL` must be a public HTTPS URL so Telegram can reach `/api/webhooks/telegram/{account}`.

Provider wiring guide: [docs/INTEGRATION_READY.md](docs/INTEGRATION_READY.md).

## Architecture Notes

Key controllers:

- `InboxController`
- `DashboardController`
- `WorkspaceController`
- `ConnectedAccountController`
- `AiSettingsController`
- `KnowledgeBaseController`
- `SettingsController`
- `WebhookController`
- `N8nController`
- `GoogleAuthController`

Key services/support:

- `CurrentBusinessService`
- `MessageIngestionService`
- `ConversationMessageService`
- `GmailConnectionService`
- `TelegramConnectionService`
- `AiReplyService`
- `MetaConnectionService`
- `ChannelResolverService`
- `InboxUi`

Key persistence additions:

- `conversation_reads` tracks per-user read state.
- `message_attachments` stores Gmail-imported attachments, Telegram media, and manually uploaded staff reply attachments.
- `saved_replies` stores reusable manual response snippets per workspace.
- `businesses.webhook_secret` stores per-workspace webhook secrets.
- `connected_accounts` supports multiple rows for the same platform in the same workspace.
- `connected_accounts.refresh_token`, `token_expires_at`, and `provider_meta` support real provider OAuth connections.
- inbox query indexes cover state/channel/recent conversation access.

Connected account behavior:

- A workspace can connect more than one Instagram, Facebook, or WhatsApp account.
- Demo connect creates a new connected account row instead of overwriting the existing platform row.
- Inbound messages can resolve the correct account using `platform + external_account_id`, with fallback support for `page_id` and `phone_number_id`.
- Disconnecting an account clears the encrypted token, sets `status` to `disconnected`, and removes it from the active Accounts page without deleting conversation history.
- Demo customer identity uses readable Instagram/Facebook usernames or WhatsApp numbers in the staff UI; real Meta integration should preserve a stable provider identifier separately if needed.
- Gmail accounts use `platform=gmail`, store encrypted access/refresh tokens, and sync recent inbox emails into `Gmail` conversations manually.
- Imported Gmail messages default to `Needs Human` and `ai_mode=human`; Gmail auto-replies are intentionally disabled until sending is implemented safely.
- Telegram accounts use `platform=Telegram`, store encrypted bot tokens, register a Bot API webhook when `APP_URL` is public HTTPS, and import customer messages into the unified inbox.
- Telegram text and media replies are sent through the Bot API to the originating chat ID. Images, videos, files, and voice/audio notes are capped at 10MB in the current UI flow.

AI settings behavior:

- `auto_reply_enabled=false` prevents demo automatic replies and sends incoming conversations to staff review.
- `human_takeover_enabled=false` hides/disables the takeover switch and blocks takeover requests.
- `business_hours_enabled=true` gates automatic replies to the current fixed demo window of 09:00-19:00 app time.
- Resuming AI mode only changes the conversation back to automation control; it does not send an immediate placeholder reply.
- Sending a manual staff reply automatically switches the conversation into human mode.

Knowledge base behavior:

- FAQs, products/services, business rules, and saved replies are edited from a focused tabbed workspace instead of one long stacked page.
- Mobile shows one active knowledge section at a time, with counts in the section tabs.
- Create, edit, and delete actions return to the relevant section after saving.

Inbox scale limits:

- conversation list renders latest 50 matching conversations
- selected thread loads latest 100 messages
- UI shows a cap notice when more conversations exist

## Local Setup

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create and configure `.env`:

```bash
copy .env.example .env
php artisan key:generate
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

Build assets:

```bash
npm run build
```

Run the app:

```bash
php artisan serve
```

For frontend development:

```bash
npm run dev
```

## Verification

Run tests:

```bash
.\vendor\bin\phpunit.bat --do-not-cache-result
```

Build assets:

```bash
npm run build
```

Compile Blade:

```bash
php artisan view:cache
```

## Demo Data

The seeder creates demo businesses, accounts, settings, knowledge-base content, customers, conversations, messages, and automation logs.

Demo login:

```text
demo@perpetualinbox.test
password
```

## Current Limitations

Still not production-complete:

- real Meta API integration
- real OpenAI integration
- real n8n workflow execution beyond compatible endpoints
- Gmail Pub/Sub push/watch support
- Gmail outbound sending from the inbox
- Telegram private-user inbox import is not supported; Telegram is bot-backed only
- Telegram media sending is implemented for bot-backed conversations, with a 10MB upload/download cap
- advanced workspace settings, billing, and danger-zone controls
- configurable business-hours schedule UI
- separate immutable provider customer IDs from display usernames/phone numbers before production if Meta payloads require both
- role-based authorization policies
- billing/subscription system
- true infinite loading for older conversations/messages
- production error pages and observability

See [docs/PROJECT_MEMORY.md](docs/PROJECT_MEMORY.md) for detailed project memory and implementation history.
