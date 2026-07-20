# Perpetual Inbox AI

Perpetual Inbox AI is the current working name for a Nigeria-first Laravel/Blade SaaS product launching under the **Perpetual Devs** brand. The final product name may change. It gives businesses one organized inbox for Instagram, Facebook, WhatsApp, Gmail, and Telegram-style customer conversations.

The channel scope is private inbox messages only. Comments, posts, public feed activity, and story replies are intentionally out of scope for now.

The unified inbox and manual team workflow are free. Revenue comes from prepaid AI credits: businesses buy credits when they want the Laravel-native AI agent to classify conversations, answer customers, and automate routine work. There is no fixed SaaS subscription in the current commercial direction.

The current product direction is calm customer communication and workflow management, not AI hype. AI assistance works in the background through the conversation state engine, while the main product value remains the unified inbox, human takeover, team visibility, customer history, and faster replies.

## Current Build Direction

Provider testing is temporarily paused, especially public Meta Embedded Signup, while the business completes Meta verification and App Review requirements. Existing Meta integration code must be preserved.

Development now proceeds in this order:

1. Add verified prepaid AI-credit purchases, payment history, and low-balance customer controls to the implemented reserve/settle/release ledger.
2. Add production observability for payment webhooks, AI recovery, queue failures, and customer-facing error states.
3. Remove or deliberately deprecate the remaining n8n compatibility endpoints after confirming no production consumer depends on them.
4. Connect Resend invitations and transactional email after an owned sending domain is available.
5. Add the customer-dashboard PWA layer after checkout and the core customer workflow are stable.
6. Package only the customer application as Android/iOS clients later; marketing, Filament owner administration, webhooks, and Laravel remain web/server surfaces.
7. Resume public Meta onboarding and App Review work after Meta verification is available.

## Current Status

Built and working as a local MVP:

- Laravel 12, Blade, Tailwind CSS, Alpine.js, Vite
- Google-only public signup/login through Laravel Socialite, with separate login and registration pages
- Public password, reset, and magic-link authentication are intentionally disabled; private Filament owner authentication remains separate
- Multi-tenant business/workspace model
- Server-enforced Owner/Admin/Agent roles: Owner has full control, Admin manages operations and agents, and Agent is inbox-only
- Secure team invitation links restricted to the exact invited Google email, with owner/admin member-management boundaries
- Session-backed active workspace resolution
- Business-scoped dashboard data
- Dashboard defaults to inbox
- Mood-board aligned WhatsApp/Instagram-inspired inbox UI with no dashboard gradients
- Lightweight SPA-style dashboard navigation for internal dashboard links/forms, including pending feedback and double-submit protection
- Demo connected accounts for Instagram and Facebook; WhatsApp uses Meta Embedded Signup
- Real Gmail OAuth connection with encrypted token storage, queued automatic scheduled/Pub/Sub sync, manual on-demand fallback sync, cleaned email rendering, clickable links, no-reply handling, and text replies through the Gmail API
- Real Telegram bot-backed connection with encrypted bot token storage, webhook registration, incoming message/media ingestion, customer profile photos, and text/media replies through the Telegram Bot API
- Multiple connected accounts per social platform per workspace
- Disconnect flow for connected accounts; disconnected records are preserved internally but hidden from the active Accounts UI
- Production-tested AI Conversation State Engine with risk-based routing
- Provider-neutral queued AI runtime with a Gemini 3.1 Flash-Lite adapter, structured state/reply decisions, business knowledge context, token/cost usage records, and guarded activation
- Atomic AI-credit grants, reservations, usage settlement, and failure releases; paid credit checkout is still pending
- Manual replies, always-available human takeover, resume automation, and close support in backend
- The consolidated AI Assistant setup controls automatic replies and business-hours gating; business facts and policies remain structured underneath
- AI handovers always send a customer acknowledgement, and the scheduler recovers stale unanswered AI-controlled chats without touching human-owned conversations
- User-dashboard Credits & Usage, Analytics, and Team pages, backed by AI wallet, transaction, and per-response usage tables
- Editable knowledge base with mobile-friendly section tabs for FAQs, products/services, business rules, and saved replies
- Secured webhook/API write routes
- Per-user conversation read tracking
- Conversation/message query limits and inbox indexes for scale
- Feature tests covering workspace isolation, inbox filtering/unread, webhooks, connected account lifecycle, and authorization
- Polished UI interactions may use focused third-party libraries when they provide better quality than hand-rolled controls. Current examples: `wavesurfer.js` powers inbox voice-note waveform recording/playback, `FilePond` handles media selection previews, and `Plyr` handles video playback UI.

Latest verification:

```bash
npm run build
.\vendor\bin\phpunit.bat --do-not-cache-result
php artisan view:cache
```

Last known passing test count: `106 tests, 478 assertions`.

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
- compact state filter row with icon counts for inbox, needs reply, AI, scheduled/waiting, and done/closed
- bottom-sheet filters for date, time of day, and sort order, applied through dashboard fetch navigation instead of a full page reload
- full-width DM-style conversation list
- mobile list-to-thread behavior
- thread view with message composer
- manual composer supports text, private file/image/audio/video attachments, attachment-only staff replies, and in-app voice-note recording
- composer image/video selection uses a preview tray instead of generic selected-file text
- message media supports images, videos, files, and voice notes with a 10MB per-file cap
- media timestamps sit under the bubble/frame, and swipe-to-reply can attach reply context to an outgoing message
- customer context panel on wide desktop
- mobile customer profile bottom sheet opened from the chat header name
- channel-aware customer identity labels: Instagram username, Facebook username, WhatsApp number, Gmail email address, or Telegram chat ID
- always-available human takeover
- top-right toast notifications that auto-dismiss after 3 seconds
- inbox polling keeps open lists and selected threads updated without manual refresh
- conversations default to `ai_mode=auto`; `Needs Human` is a status, while `human` mode is reserved for explicit takeover or disabled automation conditions

Design reference:

- [Messaging SaaS Visual Mood Board](docs/VISUAL_MOOD_BOARD.md)

## Main Routes

Public/auth (current routes; password routes are legacy and scheduled for replacement):

- `GET /`
- `GET /login`
- `GET /register`
- `GET /auth/google/redirect`
- `GET /auth/google/callback`

Workspace:

- `GET /onboarding/workspace`
- `POST /onboarding/workspace`
- `POST /workspace/switch`

Dashboard:

- `GET /dashboard` redirects to `/dashboard/inbox`
- `GET /dashboard/inbox`
- `GET /dashboard/inbox/pulse`
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
- `GET /dashboard/ai-credits`
- `GET /dashboard/analytics`
- `GET /dashboard/team`
- `POST /dashboard/team/invitations`
- `PATCH /dashboard/team/members/{member}/role`
- `DELETE /dashboard/team/members/{member}`
- `DELETE /dashboard/team/invitations/{invite}`
- `GET /team/invitations/{token}`
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
- `DELETE /dashboard/settings/workspace`

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
- `workspace.role` middleware enforces Owner/Admin/Agent access on the server; hidden navigation is only a matching UI convenience
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
META_WEBHOOK_VERIFY_TOKEN=use-a-long-random-value
META_DEVELOPMENT_CONNECT_ENABLED=false
N8N_WEBHOOK_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GMAIL_CLIENT_ID=
GMAIL_CLIENT_SECRET=
GMAIL_REDIRECT_URI="${APP_URL}/dashboard/accounts/gmail/callback"
GMAIL_PUBSUB_TOPIC=
GMAIL_PUBSUB_VERIFICATION_TOKEN=
AI_ENABLED=false
AI_PROVIDER=gemini
AI_RESERVATION_CREDITS=25
AI_TOKENS_PER_CREDIT=100
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.1-flash-lite
GEMINI_TIMEOUT=30
GEMINI_BILLING_MODE=free
TELEGRAM_API_BASE=https://api.telegram.org/bot
OPENAI_API_KEY=
```

WhatsApp Cloud API is implemented through Meta Embedded Signup on the Accounts page. The browser receives a one-time signup code; Laravel exchanges it server-side, subscribes the selected WhatsApp Business Account, encrypts the access token, and stores the account scoped to the current workspace. No Meta access token is pasted into the production UI. Its verified webhook is `${APP_URL}/api/webhooks/meta`; GET verification uses `META_WEBHOOK_VERIFY_TOKEN`, and POST requests require the Meta `X-Hub-Signature-256` signature calculated with `META_APP_SECRET`. Text replies are sent through the WhatsApp Cloud API. While Meta approval is pending, an owner/admin-only development connector can be explicitly enabled with `META_DEVELOPMENT_CONNECT_ENABLED=true` to validate and encrypt test WhatsApp, Facebook Page, and Instagram professional-account tokens. Signed Meta webhooks and staff text replies are supported for those test assets; public Facebook/Instagram OAuth onboarding still requires provider approval. The public `/privacy`, `/terms`, and `/data-deletion` pages are included for provider review; set `LEGAL_CONTACT_EMAIL=perpetualdev2@gmail.com`. Gmail is implemented for OAuth connection, manual sync, scheduled inbox polling, text replies through the Gmail API, and Pub/Sub-triggered inbox sync when `GMAIL_PUBSUB_TOPIC` and `GMAIL_PUBSUB_VERIFICATION_TOKEN` are configured. Telegram is implemented through the Bot API, not private-user MTProto sessions. For real-time provider webhooks, `APP_URL` must be a public HTTPS URL with a valid certificate.

Provider wiring guide: [docs/INTEGRATION_READY.md](docs/INTEGRATION_READY.md).

## Architecture Notes

### PWA and mobile boundary

- Laravel, provider webhooks, queues, data, and APIs remain hosted on the VPS.
- The installable PWA is limited to the authenticated customer application, starts at `/dashboard/inbox`, and uses `/dashboard/` as its intended application scope.
- Marketing routes (`/`, `/privacy`, `/terms`) and the private Filament owner panel (`/owner/*`) remain browser-only even though they live in the same Laravel repository.
- Offline support must cache only the application shell and static assets; authenticated conversations and other sensitive workspace data remain network-first and should not be persistently cached by default.
- A future Capacitor client packages only the customer experience. Android and iOS use platform-specific Google OAuth clients and system/native authentication, never an embedded WebView OAuth flow.
- Native push notifications, deep links, microphone/camera/files, and store packaging are later mobile phases. The PWA comes first.

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
- Gmail accounts use `platform=gmail`, store encrypted access/refresh tokens, and sync recent inbox emails into `Gmail` conversations manually, through scheduled `gmail:sync`, or through Gmail Pub/Sub push-triggered sync.
- Gmail Pub/Sub watches are renewed automatically by scheduled `gmail:renew-watch` when the Laravel scheduler is active.
- Imported Gmail messages default to `Needs Human` and `ai_mode=human`; staff text replies are sent through Gmail API, while Gmail file replies remain disabled until attachment sending is implemented safely.
- Gmail rendering strips noisy HTML/template markup where possible, keeps useful links clickable, detects no-reply/automated senders, and disables the composer for non-repliable threads.
- Telegram accounts use `platform=Telegram`, store encrypted bot tokens, register a Bot API webhook when `APP_URL` is public HTTPS, and import customer messages into the unified inbox.
- Telegram text and media replies are sent through the Bot API to the originating chat ID. Telegram customer profile photos are fetched where available. Images, videos, files, and voice/audio notes are capped at 10MB in the current UI flow.

AI settings behavior:

- `auto_reply_enabled=false` prevents automatic replies and sends incoming conversations to staff review.
- Human takeover and resume are always available safety actions; workspace configuration cannot disable them.
- `business_hours_enabled=true` gates automatic replies to the current fixed demo window of 09:00-19:00 app time.
- Resuming AI mode only changes the conversation back to automation control; it does not send an immediate placeholder reply.
- Sending a manual staff reply automatically switches the conversation into human mode.
- Live AI routing is contextual rather than keyword-based: harmless general knowledge, clarification, and routine engagement do not require handover.
- Handover sends an acknowledgement before `Needs Human`; `ai:recover-unanswered` checks stale unanswered AI-controlled chats every minute.

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

## Queue Workers

The app uses Laravel queues for work that should not block page loads or webhook responses: provider webhooks, Gmail sync, AI replies, outbound messages, and future Resend emails.

The real AI runtime is disabled by default in source configuration. Production enables it through environment configuration. Eligible incoming messages dispatch `ProcessAiReply` to the `ai` queue. The job requires a configured provider key and a positive workspace credit balance, reserves credits before calling the provider, records actual tokens/cost, settles successful usage, and releases reservations after provider or delivery failures.

Platform operators can grant beta/test credits without a payment gateway:

```bash
php artisan ai:credits:grant {business-id} 1000 --reference=beta-{business-id}
```

For the current low-cost Hetzner VPS class, start with three workers:

- `webhooks`: 1 worker for fast inbound provider events
- `ai`: 1 worker for slower external AI calls
- `default,sync,mail`: 1 shared worker for normal jobs, Gmail sync, and email

Commands:

```bash
php artisan queue:work --queue=webhooks --sleep=1 --tries=3 --timeout=60
php artisan queue:work --queue=ai --sleep=1 --tries=2 --timeout=180
php artisan queue:work --queue=default,sync,mail --sleep=2 --tries=3 --timeout=300
```

Production uses Supervisor programs named `perpetual-webhooks`, `perpetual-ai`, and `perpetual-sync-mail`, running as the `perpetual` system user. The Laravel scheduler must also be called every minute by that user's crontab. On the current VPS both Supervisor and the scheduler cron are installed and enabled; manual SSH worker terminals are not required.

After deploying queue-related code, gracefully reload the long-running workers from the project directory:

```bash
sudo -u perpetual php artisan queue:restart
```

Supervisor automatically starts the replacement processes. Rebuild Laravel caches first with `sudo -u perpetual php artisan optimize` only when the deployment requires refreshed application caches or configuration.

On a $6-ish shared Hetzner VPS, treat four workers as the practical ceiling. More workers can fight PHP-FPM, the database, and the OS for CPU/RAM. When queue delay grows, upgrade server resources before adding many workers. A real launch target should move to at least a 4 vCPU / 8GB RAM server before running six to eight workers comfortably.

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

The seeded customer must authenticate through a matching Google account in the public application. Password login is retained only for explicitly authorized private Filament platform-owner access.

## Current Limitations

Still not production-complete:

- Public Instagram and Messenger OAuth onboarding (development test assets can be connected manually when explicitly enabled)
- longer-term AI prompt/provider evaluation and fallback-provider policy; the current Gemini runtime is live and functional
- n8n endpoints remain for compatibility but n8n is no longer the planned automation engine
- Gmail Pub/Sub history-diff replay
- Gmail outbound attachment sending from the inbox
- Telegram private-user inbox import is not supported; Telegram is bot-backed only
- Telegram media sending is implemented for bot-backed conversations, with a 10MB upload/download cap
- Telegram profile photos only appear when the bot can fetch them from Telegram
- advanced workspace settings and billing controls
- configurable business-hours schedule UI
- separate immutable provider customer IDs from display usernames/phone numbers before production if Meta payloads require both
- transactional delivery for the implemented team invitation flow
- verified prepaid AI-credit purchases and payment-gateway integration (wallet, usage ledger, and AI enforcement are implemented)
- private Perpetual Devs platform-owner dashboard at `/owner` with SPA navigation, platform/workspace directories, conversations, customers, AI-agent settings, connection monitoring, activity/error logs, system health, activity analytics, and a pre-launch revenue/AI-credit control screen
- Resend transactional email integration
- true infinite loading for older conversations/messages
- production error pages and observability

See [docs/PROJECT_MEMORY.md](docs/PROJECT_MEMORY.md) for detailed project memory and implementation history.
