# Perpetual Inbox AI Project Memory

Last updated: 2026-07-16

## Current Instruction

The Laravel/Blade MVP is now an active working demo for a Nigeria-first SaaS launching under the **Perpetual Devs** brand. "Perpetual Inbox AI" is a working project name, not necessarily the final product name. Continue tightening the product around the free unified inbox, customer communication workflow, business isolation, team access, and the paid AI Conversation State Engine.

Current build priority:
- Pause active Meta test-case work and public Embedded Signup testing until Perpetual Devs can complete Meta business verification and App Review. Preserve all existing Meta code and both connection lanes.
- Google OAuth is the only public customer sign-up/login method. Public email/password, password reset, password update, email magic-link, and account-deletion-password screens are intentionally disabled.
- Configure Resend later for transactional product emails and use queued Laravel notifications/mailables; Resend is not an authentication dependency.
- Complete workspace team invitations and enforce Owner/Admin/Agent permissions with policies and middleware.
- Continue the separate private Filament platform-owner dashboard for Perpetual Devs; its secure foundation, global statistics, directories, and workspace suspension controls are implemented, while credit/revenue/health modules remain pending.
- Plan a customer-dashboard PWA after the core workflow is stable. Its start URL is `/dashboard/inbox` and its intended scope is `/dashboard/`; do not include marketing pages or `/owner` in the installed customer app.
- A later Capacitor Android/iOS client packages only the customer experience. Laravel, queues, webhooks, APIs, marketing pages, and the Filament owner panel remain hosted/web surfaces. Mobile Google authentication must use separate platform OAuth clients and native/system-browser flows, never embedded WebView OAuth.
- Implement prepaid AI credits and an immutable usage ledger. The unified inbox/manual workflow is free; customers pay only for AI-agent usage. There is no fixed recurring SaaS fee in the current direction.
- Scrap n8n as the planned automation bridge. Laravel is the complete application and automation brain using services, jobs, queues, events, tools, and scheduled commands. Existing n8n-compatible endpoints may remain for backward compatibility until deliberately removed.
- Build a provider-neutral Laravel AI layer. Gemini 2.5 Flash-Lite is the current cost-first default candidate for routine DM replies; Gemini Flash and OpenAI may be configurable quality/fallback providers. Do not hard-wire business logic to one provider.
- Keep the inbox as the default dashboard screen.
- Keep every business-owned query scoped by `business_id`.
- Preserve the calm, mature workflow-product tone. Avoid AI hype in public-facing copy.
- Gmail OAuth, scheduled polling, Pub/Sub push sync, text replies, and Telegram Bot API remain the first real provider connections. Production Meta onboarding is paused pending verification, not discarded.
- Maintain the current mood-board aligned DM-style inbox UI unless the user explicitly asks for a redesign.
- Keep dashboard surfaces flat and premium. Do not add gradients to dashboard pages, tabs, cards, loading states, or channel badges.
- Use `docs/INTEGRATION_READY.md` as supporting integration history, but prefer this memory when it conflicts with the new Laravel-only direction.
- Connected accounts must support multiple accounts per platform per workspace. Disconnect should preserve records internally but hide disconnected accounts from the active Accounts UI.
- Workspace owners have a Settings danger-zone flow for permanent deletion. It requires the exact workspace name plus `DELETE`, cascades all workspace-owned database records and encrypted connection credentials, deletes stored attachment/avatar files, preserves users who still have another workspace, and deletes/logs out the final non-platform-owner user.
- `php artisan users:merge {source-email} {target-email} --force` safely consolidates an obsolete/demo user into a Google-authenticated user. It transfers ownership, keeps the strongest workspace role, preserves connected accounts, merges conversation-read state and invitation authorship, then deletes only the source user. Use this for the `demo@perpetualinbox.test` to real-Google-account migration before reconnecting providers.
- Gmail ownership protection is implemented: one Gmail identity can only be actively connected to one workspace. Reconnects in that workspace are allowed; disconnecting releases it for another workspace. Deliberate owner-controlled transfer remains a later enhancement.
- The business dashboard now includes Credits & Usage, Analytics, and Team. AI wallets, credit transactions, and per-response token/cost usage records have real database foundations ready for the Laravel AI agent and future checkout integration.
- AI settings must be behavior-backed, not cosmetic. Auto reply, human takeover, and business-hours settings must affect ingestion and inbox actions.
- Conversations should default to `ai_mode=auto`. `Needs Human` is a queue/status, while `ai_mode=human` is reserved for explicit takeover, manual staff replies, or disabled automation conditions.
- Staff-facing customer identity should show Instagram/Facebook usernames, WhatsApp phone numbers, Gmail email addresses, or Telegram chat IDs where available. Add a separate stable provider ID later if real provider payloads require both identity matching and friendly display.
- For polished UI/design interactions, prefer proven focused libraries/components over custom hand-built controls when a library gives better quality, accessibility, or maintainability. Keep all third-party UI aligned with `docs/VISUAL_MOOD_BOARD.md`; do not import a library's default visual style blindly.
- Do not push to GitHub unless the user explicitly asks for `push`.
- Preserve two Meta connection lanes: production/customer WhatsApp Embedded Signup and an explicitly enabled owner/admin-only development connector for test WhatsApp, Facebook Page, and Instagram professional assets. `META_DEVELOPMENT_CONNECT_ENABLED` defaults to false.

## Product Brief

Build a complete MVP SaaS web app called "Perpetual Inbox AI".

It is a unified customer communication inbox for businesses. Businesses sign up, create a workspace, connect Instagram/Facebook/WhatsApp/Gmail/Telegram accounts, add FAQs/business rules, invite staff, and manage customer conversations from one dashboard. AI assistance should feel like background workflow support, not the core marketing headline. Laravel is the only application brain and source of truth; n8n is no longer part of the planned production architecture.

Channel scope: private inbox messages only. Do not ingest comments, posts, public feed activity, or story replies unless product scope changes later.

The free product includes the unified inbox and manual team workflow. Monetization is through prepaid AI credits consumed by AI-agent actions. Provider costs and customer charges must be recorded independently so pricing can change without corrupting usage history.

## Commercial Model and AI Credits

- Market: Nigeria first; prices and payment UX should be presented in naira while provider costs are also recorded in their original currency and normalized for reporting.
- No fixed subscription fee in the current direction.
- Unified inbox, connected accounts, manual replies, and normal team workflow are free, subject to reasonable abuse and infrastructure limits.
- AI automation requires a positive prepaid credit balance.
- Customers see understandable AI credits and usage estimates, not raw provider tokens as the primary unit.
- Internally record provider, model, input tokens, cached tokens, output/reasoning tokens, provider-reported usage, original provider cost, exchange-rate snapshot, customer credit charge, and margin.
- Use an immutable ledger for purchases, promotional grants, reservations, usage deductions, reversals, refunds, and manual adjustments.
- Check/reserve sufficient credits before an AI job, then settle against actual provider-reported usage. Failed provider calls must not permanently consume credits.
- When credits are exhausted, AI pauses safely and routes work to humans; the free inbox must continue functioning.
- Routine classification and reply generation should be combined into one structured AI call where practical to reduce cost.
- Limit history, retrieve only relevant knowledge, cap output, and avoid unnecessary tool loops.
- Gemini 2.5 Flash-Lite is the current default candidate because of its low text-token cost. The provider layer must allow later switching, fallback, and per-workspace model policy.

## AI Conversation State Engine

Replace the traditional inbox architecture with an AI Conversation State Engine.

Every conversation must always belong to one of four states:
- `AI Handling`
- `Waiting`
- `Needs Human`
- `Closed`

These states are represented throughout the application using colors:
- Green: `AI Handling`
- Yellow: `Waiting`
- Red: `Needs Human`
- Grey: `Closed`

Every incoming message should be processed by `AiReplyService`.

The AI decides whether it should:
- Continue handling the conversation.
- Wait for the customer.
- Escalate to a human.
- Close the conversation.

When the AI determines that human judgment is required, it must:
- Stop generating further replies.
- Change the conversation status to `Needs Human`.
- Display a red status indicator throughout the UI.

The AI should escalate conversations when:
- Customer requests a discount.
- Customer files a complaint.
- Customer requests a refund.
- Customer asks for a custom quotation.
- Customer requests manager approval.
- AI confidence falls below a configurable threshold.
- Request is outside the business knowledge base.
- Business rules require human approval.

The state engine should make the inbox operationally focused: staff should immediately see which conversations are being handled by AI, which are waiting, which need human attention, and which are closed.

## Tech Stack

- Laravel latest stable supported by this machine
- Laravel Blade
- Tailwind CSS
- Alpine.js
- SQLite for now
- Laravel Breeze components/session guard where still useful, without public password authentication
- Laravel Socialite for Google OAuth
- Resend through Laravel's mail transport
- Passwordless one-time signed magic links as the non-Google fallback
- Vite
- No React
- No Vue
- Reusable Blade components
- Fully responsive desktop and mobile

Note: Composer selected Laravel 12 because Laravel 13 requires PHP 8.3 and this machine currently has PHP 8.2.12.

## Design Direction

Use a clean mature SaaS UI inspired by Front, Intercom, Help Scout, Linear, Attio, Notion, WhatsApp, and Instagram DM workflows. The public landing page should position the product as calm customer communication and workflow management:

- Main headline: "Every customer conversation, organized in one inbox."
- Do not make the landing page scream AI.
- Avoid robot/brain illustrations, glowing gradients, futuristic/cyber visuals, excessive green, and repeated "powered by AI" language.
- Emphasize unified inbox, team workflow, faster replies, human takeover, smart routing, customer history, and never missing enquiries.
- Use `docs/VISUAL_MOOD_BOARD.md` as the extracted visual mood board reference for palette, typography, components, iconography, and spacing direction.

Dashboard/inbox direction:
- Desktop keeps a permanent dark side rail.
- Mobile uses a menu icon to open/close the sidebar.
- `/dashboard` defaults to `/dashboard/inbox`.
- Inbox is full-bleed inside the dashboard shell.
- Conversation list follows WhatsApp/Instagram DM structure: search, social-channel filters, state filters, full-width list rows, social platform logo badges, unread count, and no framed card around the list.
- The upper inbox filter area uses a compact search row, brand-colored platform strip, and compact state strip. State icon/count colors should stay consistent between header counts and row badges.
- Extra inbox filters live in a bottom sheet opened from the filter icon. The bottom sheet contains Date, Time, and Sort by controls only; platform filtering stays in the top platform strip. These filters should apply through the dashboard fetch flow without a full reload.
- Mobile inbox behaves like normal DM apps: list first, tap chat to open thread, use back button to return.
- Mobile thread headers let staff tap the customer name to open a compact customer profile bottom sheet.
- Composer controls support text replies, private file/image/audio/video uploads, in-app voice-note recording, and attachment-only staff replies.
- Human takeover/pause automation should be a compact icon action in the composer, not a large switch or pill button.
- Status messages appear as top-right toast notifications that auto-dismiss after 3 seconds.
- Voice-note recording and playback should use a polished waveform pattern. Current implementation uses `wavesurfer.js` plus its Record plugin, styled to the project mood board.
- Image/video selection should use a polished preview tray instead of selected-file text. Current implementation uses `FilePond`, styled to the project mood board.
- Video playback should not use raw browser-default controls. Current implementation uses `Plyr`, styled to the project mood board.
- Message timestamps should sit under the text/media frame, not inside the frame. Media frames should stay compact and avoid large empty padding.
- Swipe-to-reply is supported in the thread UI and stores reply context on the outgoing message metadata.

## Core Multi-Tenant Requirement

One Laravel app must support many businesses. Each business/workspace must have its own dashboard data, connected accounts, inbox conversations, customers, AI settings, FAQs, products/services, business rules, team members, and automation logs.

Every query must be scoped by `business_id`. When a user logs in, detect their active business/workspace and show only data belonging to that business. Add a workspace switcher in the topbar for users who belong to multiple businesses. Use middleware or helper methods to resolve the current business.

Example isolation:
- VIP Rentals should only see VIP Rentals messages/settings.
- Lagos Detailing should only see Lagos Detailing messages/settings.
- They must never see each other's conversations, customers, FAQs, connected accounts, or logs.
- A single workspace may have multiple connected accounts for the same platform, such as three WhatsApp Business numbers or two Instagram accounts. Account routing must use account identifiers, not only platform.
- WhatsApp real onboarding uses Meta Embedded Signup. The browser receives a one-time code plus WABA/phone IDs, while Laravel exchanges the code, subscribes the WABA, encrypts the token, and scopes the connection to the current business. The UI must never ask users to paste Meta access tokens.
- Public provider-review pages are available at `/privacy`, `/terms`, and `/data-deletion`; production must set `LEGAL_CONTACT_EMAIL` to a monitored address.

## Auth Requirements

Target public authentication:
- Google OAuth login/sign-up using Laravel Socialite is the only public authentication method.
- Do not add passwordless email magic links unless the product direction changes.
- Do not expose public email/password registration, login, forgot-password, reset-password, or password-update flows.
- Keep logout, secure session regeneration/invalidation, rate limiting, and login audit events.
- Microsoft sign-in may be added later if customer demand justifies the extra provider.
- Resend delivers magic links, workspace invitations, welcome/onboarding messages, credit/payment notices, low-credit warnings, important connection failures, and security notifications.
- Magic links and invitations must be hashed at rest where applicable, single-use, short-lived, rate-limited, and invalidated after use.

Google routes:
- `GET /auth/google/redirect`
- `GET /auth/google/callback`

Google behavior:
- If user signs up with Google, create user automatically.
- If Google email already exists, safely link Google provider to existing user.
- Store `google_id`.
- Store `avatar` if available.
- Never store Google access token unless needed.
- Treat the provider-verified Google email as verified.
- Record last login time/IP and security/audit events.

User columns:
- `google_id` nullable
- `avatar` nullable
- `email_verified_at`
- `last_login_at`
- `last_login_ip`

Auth status as of 2026-07-17:
- `GoogleAuthController` performs the Socialite redirect/callback, safely links by Google ID or normalized email, records login metadata, and routes new users to workspace onboarding.
- The `User` model implements `MustVerifyEmail`; Google-provided identities are marked verified.
- Public Breeze password routes are disabled. The private Filament platform-owner login remains separate so Perpetual Devs administration is not locked out.
- Team invitation acceptance and consistent role enforcement are not complete.

## Platform Owner Dashboard

Build a separate private Perpetual Devs operator panel, not a customer workspace page. It should cover:
- users, businesses, team membership, suspensions, and support lookup;
- AI credit balances, purchases, adjustments, usage, provider costs, revenue, and gross margin;
- provider health, failed jobs/webhooks, connected-account failures, queues, and email delivery status;
- plans/limits only if later required, without forcing a recurring subscription model;
- audit/security events and safe workspace inspection without bypassing tenant isolation accidentally.

Use a proven Laravel admin-panel library such as Filament if its installed version is compatible with this Laravel/PHP stack. Keep it private and visually separate from the custom customer-facing dashboard.

## Team Roles and Invitations

- Workspace creator becomes Owner.
- Owner has full workspace, team, credits/payment, integrations, and danger-zone control.
- Admin manages inbox, knowledge, team, settings, and integrations except ownership transfer and owner-only financial/danger actions.
- Agent accesses inbox/customer work only and must not access connected-account credentials, AI configuration, credits/payment administration, or workspace danger controls.
- Invitations are emailed through Resend, expire, are single-use, and are accepted after Google authentication.
- Enforce roles through policies/middleware and business-scoped queries, not only hidden navigation.

## Security Requirements

Implement seriously:
- No public passwords in the target flow; preserve secure handling for any temporary legacy/demo credentials until removed
- CSRF protection on all forms
- Validation on every request
- Authorization checks/policies for business-owned resources
- Middleware to ensure user belongs to selected workspace
- Every query scoped by `business_id`
- Prevent URL ID manipulation from exposing another business's data
- Encrypt connected account `access_token`
- Never expose `access_token` in Blade or API responses
- Rate limit OAuth initiation/callback handling, magic-link requests/redemptions, invitations, AI generation, and webhook endpoints
- Webhook secret verification for n8n/API endpoints
- Per-business webhook secrets with global secret fallback
- Per-user conversation read tracking
- Input validation and sanitization where needed
- Escape all Blade output
- Use fillable/guarded properly
- Secure sessions
- Basic error pages: 403, 404, 500

Role model is planned but not fully enforced yet:
- Owner: full access
- Admin: manage settings, accounts, inbox, team except owner-only billing/danger zone
- Agent: inbox and customers only

Agents must not access AI settings, connected account tokens, billing, or workspace danger zone.

Audit/automation logs should be created for login, logout, account connected, account disconnected, AI settings changed, team member invited, role changed, webhook failed, incoming message received, AI reply generated, and outgoing message saved.

## Routes To Support

Public and auth:
- `/`
- `/login`
- `/register` (legacy; remove from the public flow after passwordless auth is complete)
- `/forgot-password` (legacy; remove with public passwords)
- `/auth/google/redirect`
- `/auth/google/callback`

Onboarding:
- `/onboarding/workspace`

Dashboard:
- `/dashboard`
- `/dashboard/inbox`
- `GET /dashboard/inbox/pulse`
- `/dashboard/accounts`
- `GET /dashboard/accounts/gmail/redirect`
- `GET /dashboard/accounts/gmail/callback`
- `POST /dashboard/accounts/gmail/{account}/sync`
- `PATCH /dashboard/accounts/{account}/disconnect`
- `/dashboard/ai-settings`
- `/dashboard/knowledge-base`
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
- `/dashboard/settings`
- `PATCH /dashboard/settings/business`

API:
- `POST /api/n8n/incoming-message`
- `POST /api/n8n/save-outgoing-message`
- `POST /api/n8n/log-event`
- `POST /api/webhooks/meta`
- `POST /api/incoming-message`
- `POST /api/generate-ai-reply`
- `POST /api/save-outgoing-message`

n8n endpoints require `X-N8N-SECRET`, compare it with `N8N_WEBHOOK_SECRET` or `APP_WEBHOOK_SECRET`, return 401 if invalid, are API-rate-limited, and validate request data.

Gmail automatic polling:
- `php artisan gmail:sync` syncs connected Gmail inbox accounts.
- Laravel scheduler runs `gmail:sync --mailbox=inbox --limit=20` every minute when the VPS cron calls `php artisan schedule:run`.
- Gmail Pub/Sub push support exists at `POST /api/webhooks/gmail/pubsub?token={GMAIL_PUBSUB_VERIFICATION_TOKEN}`.
- Laravel scheduler runs `gmail:renew-watch` daily at 02:15 and renews connected Gmail Pub/Sub watches when missing or within one day of expiry. The VPS scheduler cron must remain active.
- When `GMAIL_PUBSUB_TOPIC` is configured, Gmail connect attempts to register `users/me/watch` for inbox changes.
- Pub/Sub currently triggers the existing recent-inbox sync path for the matching connected account; cron polling should remain as a safety fallback.

Public webhook endpoints require `X-WEBHOOK-SECRET` or `X-META-SECRET`. They prefer the business-specific `businesses.webhook_secret` when a request is tied to a business/conversation, with `APP_WEBHOOK_SECRET`/`META_WEBHOOK_SECRET` as global fallback.

## Current Page Scope

Landing page:
- Current headline: "Every customer conversation, organized in one inbox."
- Current subtext: "Bring Instagram, Facebook, WhatsApp and Gmail messages into one workspace. Let routine enquiries flow smoothly while your team focuses on conversations that need attention."
- CTAs: Start Free Trial/Open Dashboard, View Demo
- Tone: calm, mature, business-friendly, communication/workflow product.
- Feature framing: unified inbox, color-coded conversation states, human takeover, team visibility, customer history, smart assistance in the background.

Dashboard layout:
- Sidebar: Inbox, Accounts, AI Settings, Knowledge Base, Settings
- No Home tab, Customers tab, Team tab, or Logs tab in the current dashboard.
- Logout sits at the bottom of the side panel.
- Mobile: collapsible sidebar and usable DM-style inbox.

Feature pages:
- Dashboard home redirects to inbox by default.
- 3-column inbox with conversation list, thread, media-capable composer, and customer details
- Connected accounts with fake Meta-channel connection flow, real Gmail OAuth/manual and scheduled sync, Gmail text replies, multiple accounts per platform, active-only account list, disconnect support, and no intro/explainer header block
- AI settings with fake preview reply
- Editable knowledge base sections for FAQs, products/services, business rules, and saved replies. Mobile should show one active section at a time through section tabs, not a long stacked page of every form.
- Settings with editable business profile and workspace summary. Internal API, billing, and danger-zone placeholders are intentionally hidden from normal workspace users for now.

Inbox state UI:
- At the top of the conversation list, create horizontal filter tabs similar to the X/Twitter timeline.
- Tabs: `All`, `Needs Human`, `AI Handling`, `Waiting`, `Closed`.
- Each tab should show a live conversation count.
- Each conversation row should display a colored status indicator.
- Example row indicators: red `Needs Human`, green `AI Handling`, yellow `Waiting`, grey `Closed`.
- The currently selected tab should only display conversations belonging to that state.

Human takeover:
- Human takeover is displayed as a modern switch control.
- Off state posts to `dashboard.inbox.take-over`.
- On state posts to `dashboard.inbox.resume-ai`.
- Resuming AI should not automatically send a message. It only returns the conversation to automation control; later OpenAI/state-engine logic should decide whether a reply is needed.
- Staff may reply manually, and any manual staff reply should automatically switch the conversation to human mode.
- The close backend route still exists, but the visible close composer button was removed from the current UI.

## Database Tables

Tables from the brief:
- `users`
- `businesses`
- `business_user`
- `connected_accounts`
- `conversations`
- `messages`
- `ai_settings`
- `faqs`
- `products`
- `business_rules`
- `saved_replies`
- `customers`
- `team_invites`
- `automation_logs`
- `conversation_reads`
- `message_attachments`

Structural migrations for these tables have been created. Additional production-readiness migrations add inbox query indexes, per-user conversation read receipts, and per-business webhook secrets.

Connected account behavior:
- `connected_accounts` intentionally allows multiple rows with the same `business_id` and `platform`.
- Active account routing should use `platform + external_account_id`; inbound payloads may also supply `page_id` or `phone_number_id`.
- Disconnect sets `status` to `disconnected`, clears `connected_at`, clears encrypted `access_token`, and logs `account_disconnected`.
- Disconnected account rows are retained for audit/history and conversation safety, but are not shown on the active Accounts page.
- Demo connect counts only active accounts when naming new demo accounts.
- Gmail uses `platform=gmail`; synced conversations use channel `Gmail`.
- Gmail access and refresh tokens are encrypted; `provider_meta` stores non-secret profile/scope metadata.
- Gmail sync imports recent inbox emails manually or through the scheduler, skips duplicate Gmail message IDs, and defaults imported email threads to `Needs Human`/`ai_mode=human`.

Knowledge base behavior:
- FAQs, products/services, business rules, and saved replies can be created, edited, and deleted from the dashboard.
- Knowledge base mutations are scoped to the active workspace and reject foreign-business records.
- `saved_replies` stores reusable manual responses with optional shortcuts.
- The Knowledge Base page uses server-side section tabs (`faqs`, `products`, `rules`, `saved-replies`) so mobile users edit one focused content type at a time.

## Controllers

Created:
- `LandingController`
- `DashboardController`
- `WorkspaceController`
- `InboxController`
- `ConnectedAccountController`
- `AiSettingsController`
- `KnowledgeBaseController`
- `SettingsController`
- `GoogleAuthController`
- `WebhookController`
- `N8nController`

Removed from current dashboard scope:
- `CustomerController`
- `TeamController`
- `AutomationLogController`

Current status: primary MVP controller logic is implemented for dashboard inbox, account demo connection, AI settings, knowledge base, settings, workspace creation/switching, webhooks, and n8n-compatible endpoints.

## Service Classes

Created:
- `AiReplyService`
- `ChannelResolverService`
- `MessageIngestionService`
- `MetaConnectionService`
- `GmailConnectionService`
- `CurrentBusinessService`
- `ConversationMessageService`
- `InboxUi`

Current status:
- `AiReplyService` contains demo state-decision and placeholder reply behavior.
- `MessageIngestionService` creates/updates accounts, customers, conversations, messages, and automation logs for incoming demo/API messages.
- `MessageIngestionService` resolves connected accounts by `business_id + platform + external_account_id`, with payload fallbacks for `page_id` and `phone_number_id`, so multiple same-platform accounts can coexist.
- `MessageIngestionService` honors AI settings: disabled auto reply sends incoming conversations to staff review, enabled business-hours mode only auto-replies inside the fixed demo reply window, and AI-escalated conversations remain in `ai_mode=auto` unless automation is explicitly disabled.
- `ConversationMessageService` centralizes outgoing message writes and read-receipt marking.
- `CurrentBusinessService` handles active workspace resolution and switching.
- `InboxUi` holds inbox presentation metadata for states, social channels, and intent labels.
- `GmailConnectionService` handles Gmail OAuth redirect URL building, auth-code token exchange, Gmail profile fetch, access-token refresh, recent inbox sync, email parsing, duplicate detection, and local inbox import.
- `TelegramConnectionService` handles Bot API webhook registration, incoming message/media normalization, customer profile photo fetch, text replies, and media replies for bot-backed Telegram conversations.

Knowledge base CRUD pass on 2026-07-08:
- Reworked the Knowledge Base page from read-only demo cards into editable workspace content.
- Added create/update/delete flows for FAQs, products/services, business rules, and saved replies.
- Added `saved_replies` table and `SavedReply` model.
- Added feature tests for knowledge base CRUD and cross-workspace mutation protection.
- Latest verification after this pass: PHPUnit passes with `67 tests, 254 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Settings cleanup pass on 2026-07-08:
- Removed the user-facing API/billing placeholder card from the Settings page.
- Added editable business profile fields for name, category, email, phone, website, and description.
- Added `PATCH /dashboard/settings/business` for scoped workspace profile updates.
- Added feature tests confirming settings profile updates and that API/billing placeholder copy is not shown.
- Latest verification after this pass: PHPUnit passes with `69 tests, 268 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Knowledge base mobile refinement pass on 2026-07-08:
- Reworked the Knowledge Base page into focused server-side section tabs so mobile users only see one editable content type at a time.
- Section tabs include live counts and return users to the relevant section after create, update, or delete actions.
- Updated feature coverage for the tabbed Knowledge Base behavior.
- Latest verification after this pass: PHPUnit passes with `69 tests, 272 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Inbox composer media pass on 2026-07-08:
- Replaced the large human/AI mode switch in the composer with a compact pause-automation icon action.
- Added manual staff reply uploads for files, images, and audio notes using private `message_attachments` storage.
- Attachment-only staff replies are allowed.
- Latest verification after this pass: PHPUnit passes with `70 tests, 276 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Inbox media and voice-note pass on 2026-07-13:
- Added Telegram media receive/send support through the existing `message_attachments` pipeline.
- Media now covers images, videos, files, audio, and voice notes with a 10MB cap.
- Telegram incoming media is downloaded into private storage and rendered in the inbox through authenticated attachment routes.
- Telegram outgoing media is delivered through the Bot API using `sendPhoto`, `sendVideo`, `sendVoice`, or `sendDocument` as appropriate.
- Image previews no longer show a duplicate download row and open into a larger in-page preview when clicked.
- Recorded voice notes are created inside the app with the `wavesurfer.js` Record plugin; pressing Send while recording automatically stops, attaches, and sends the note.
- Voice-note playback uses `wavesurfer.js` for a compact waveform player instead of the browser default audio control.
- Recorded voice-note attachments are explicitly marked as `media_type=voice` so they do not render as video even when the browser stores them as WebM.
- Image/video upload selection now uses `FilePond` with preview, type validation, 6-file limit, and 10MB-per-file validation.
- Inline video playback now uses `Plyr` instead of raw browser-default video controls.
- Latest focused verification after this pass: `npm run build` passes and Telegram feature tests pass with `13 tests, 57 assertions`.

Inbox live-update, filter, and media refinement pass on 2026-07-13:
- Added inbox pulse polling so new Telegram messages, selected-thread updates, and unread counts can appear without manually refreshing the page.
- Unread badges now count unread messages, not only unread conversations.
- Removed the repeated fixed Telegram auto-reply behavior; incoming Telegram messages should not trigger a canned response by default.
- Added Telegram customer profile photo fetching and display where Telegram exposes an avatar.
- Added top inbox filters for date, time of day, and sort order while keeping platform filtering in the horizontal channel strip.
- Replaced default browser select controls in the filter sheet with custom Alpine dropdown controls.
- Restored the compact platform strip and compact state strip after a heavier header redesign was rejected.
- Kept the filter bottom sheet focused on Date, Time, and Sort by; no platform selector inside the bottom sheet.
- Refined image/video/audio message presentation to avoid oversized empty frames and duplicate inline media overlays.
- Media preview should open one viewer at a time, not allow stacked image/video overlays.
- Video playback should enlarge in a controlled viewer and close back to the chat without distorting the chat layout.
- Voice notes should use a compact waveform player similar to modern chat apps, while still following the project mood board colors.
- Text and media timestamps render under the bubble/frame.
- Swipe-to-reply is available for selecting a specific message as reply context.
- Latest focused verification after this pass: `npm run build` passes and `php artisan view:cache` passes.

## Seed Demo Data To Build Later

Seeder currently includes:
- Demo user: `demo@perpetualinbox.test` / `password`
- Demo businesses: VIP Rentals and Lagos Detailing
- Attach demo user as Owner to both businesses
- Connected/demo social accounts
- Demo AI settings for each
- FAQs, products, business rules, customers, conversations, messages, and automation logs

The workspace switcher must switch between VIP Rentals and Lagos Detailing, showing different data for each.

## Blade Components To Build Later

Required reusable components:
- `AppLayout`
- `AuthLayout`
- `Sidebar`
- `Topbar`
- `StatCard`
- `ChannelCard`
- `ConversationListItem`
- `ChatBubble`
- `EmptyState`
- `Modal`
- `Badge`
- `ToggleSwitch`
- `SettingsSection`
- `Button`
- `Input`
- `Textarea`

## Environment Variables

Documented placeholders:
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`
- `GMAIL_CLIENT_ID`
- `GMAIL_CLIENT_SECRET`
- `GMAIL_REDIRECT_URI`
- `APP_WEBHOOK_SECRET`
- `META_WEBHOOK_SECRET`
- `OPENAI_API_KEY`
- `N8N_WEBHOOK_SECRET`
- `N8N_BASE_URL`

Integration readiness guide:
- `docs/INTEGRATION_READY.md`

## Progress So Far

Completed in this setup pass:
- Created Laravel project in `C:\Users\USER\no-name`.
- Installed Laravel 12.x because PHP is 8.2.12.
- Installed Laravel Breeze Blade auth scaffold.
- Installed Laravel Socialite.
- Installed npm dependencies generated by Breeze.
- Confirmed Vite build ran once through Breeze installer.
- Added Google OAuth environment placeholders and config mapping.
- Added structural web routes and API routes from the brief.
- Added `routes/api.php` to Laravel bootstrap routing.
- Created initial named controllers and services.
- Created current-business middleware alias `current.business`.
- Created structural migrations for the multi-tenant database shape.
- Added model fillable/cast placeholders, including encrypted `connected_accounts.access_token`.
- Preserved the product direction and build memory in this file.
- Added the AI Conversation State Engine requirement to memory on 2026-07-04.

Completed in the first MVP build pass on 2026-07-04:
- Implemented current workspace resolution with session-backed active business selection.
- Added workspace switching from the dashboard topbar.
- Added business relationship methods across the main models.
- Added conversation state constants for `AI Handling`, `Waiting`, `Needs Human`, and `Closed`.
- Replaced placeholder dashboard controllers with business-scoped data queries.
- Built the SaaS dashboard shell with sidebar navigation and topbar workspace switcher.
- Built dashboard home with operational stats, recent conversations, and setup checklist.
- Built the inbox page with state filter tabs, live counts, colored status indicators, conversation list, thread, composer, and customer details.
- Implemented human takeover, resume AI, close conversation, and manual reply actions.
- Implemented fake AI state decisions in `AiReplyService`.
- Implemented demo message ingestion in `MessageIngestionService`.
- Implemented basic API endpoints for incoming messages, AI reply preview, outgoing messages, and n8n secret-gated endpoints.
- Built connected accounts page with fake account connection flow.
- Built AI settings page with persisted settings updates.
- Built knowledge base and workspace settings pages.
- Customers, team, and automation logs pages were later removed from the dashboard scope.
- Built onboarding workspace creation.
- Replaced the default Laravel welcome page with the Perpetual Inbox AI landing page.
- Seeded demo user `demo@perpetualinbox.test` / `password`.
- Seeded demo businesses VIP Rentals and Lagos Detailing with accounts, settings, FAQs, products/services, business rules, customers, conversations, messages, and logs.
- Verified `npm run build` passes.
- Verified Blade templates compile with `php artisan view:cache`.
- Verified PHPUnit passes with `.\vendor\bin\phpunit.bat --do-not-cache-result`.

UI correction pass on 2026-07-04:
- Reworked the dashboard shell after the first UI pass was rejected as too bare.
- Added a darker operational sidebar, stronger product identity, active workspace panel, and improved navigation treatment.
- Added shared CSS component classes for app shell, nav links, metric cards, content cards, state pills, and state dots.
- Redesigned the dashboard home with a stronger command-center header, state summary panel, richer metric cards, improved recent conversation rows, and clearer setup checklist.
- Redesigned the inbox page with a denser operational layout, queue rail, improved state tabs, stronger thread header, better message surfaces, and customer profile cards.
- Replaced the plain landing page with a full-viewport product-scene hero, stronger brand header, CTA treatment, state-engine product preview, channel positioning, and a visible feature band below the fold.
- Added lightweight SPA-style dashboard navigation in `resources/js/app.js`: internal dashboard links and non-logout dashboard forms fetch the next Blade response, swap the app shell, update browser history, and avoid full-page reloads.
- Fixed dashboard responsiveness: desktop keeps a permanent side rail; mobile uses a menu button that opens/closes the sidebar as a drawer with backdrop.
- Replaced sidebar letter badges with modern inline line icons across dashboard navigation and added icon treatment for logout/menu controls.
- Updated the inbox to follow an Instagram/WhatsApp DM pattern: full-width state tabs, no framed border around the conversation list, and mobile list-to-thread navigation with a back button instead of showing list and chat together.
- Refined the inbox again against a WhatsApp reference: dark list and thread UI, rounded search bar, pill filters, archived row, compact circular-avatar chat rows, dark chat header action icons, textured chat background, message bubbles, and rounded composer.
- Made the inbox route full-bleed inside the dashboard content area so it no longer sits inside the standard page padding/max-width frame.
- Added an inbox-specific dark shell theme so the sidebar, active nav, topbar, workspace switcher, logout button, and inbox surface use one coherent dark/green palette instead of mixing blue, white, and chat colors.
- Applied the attached premium redesign brief: the dashboard now uses a global dark-first theme, reduced blue dominance, emerald/coral/amber/slate state colors, cohesive sidebar, AI-assisted three-zone inbox, useful customer context panel, dark account cards, AI teammate settings, dark knowledge base, and settings pages.
- Verified the redesign with `npm run build` and `php artisan view:cache`.

Major product/UI refinement pass on 2026-07-06:
- Repositioned landing page away from AI hype and toward calm customer communication/workflow management.
- Updated landing headline to "Every customer conversation, organized in one inbox."
- Removed Home tab from dashboard navigation.
- Removed Customers, Team, and Logs tabs, their dashboard routes, their Blade screens, and their orphaned controllers.
- Made `/dashboard` redirect to `/dashboard/inbox`.
- Reworked inbox into a full-width dark WhatsApp/Instagram-style DM interface.
- Added social-channel filtering for All, Instagram, WhatsApp, and Facebook.
- Replaced initial-avatar row badges with colored social platform logo badges.
- Removed extra status dots from channel logos.
- Replaced Take Over/Resume AI pill buttons with a switch-style Human takeover control.
- Removed the visible Close button from the composer action row.
- Added top-right toast notifications that auto-dismiss after 3 seconds.
- Added search behavior that preserves selected state/channel context.
- Added SPA-style dashboard navigation for internal dashboard links/forms.

Backend/security/scalability pass on 2026-07-06:
- Secured public webhook write routes with `X-WEBHOOK-SECRET` / `X-META-SECRET`.
- Kept n8n routes gated by `X-N8N-SECRET`.
- Registered the missing `api` rate limiter used by `throttle:api`.
- Added per-business `webhook_secret` with global `APP_WEBHOOK_SECRET` / `META_WEBHOOK_SECRET` fallback.
- New workspaces receive generated `whsec_...` secrets.
- Added `conversation_reads` for per-user read tracking.
- Opening a conversation explicitly marks it read for the current user.
- Added inbox query indexes for business/status/channel/recent access.
- Limited inbox list rendering to latest 50 matching conversations.
- Limited selected thread history to latest 100 messages.
- Added a visible cap notice when more conversations exist.
- Added `ConversationMessageService` for shared outgoing message writes and read marking.
- Added `InboxUi` support class so channel/status/intent presentation metadata is no longer defined inside the Blade view.
- Added feature tests for webhook auth, workspace switching, inbox search/channel filters, read receipts, default dashboard redirect, and cross-business inbox mutation denial.
- Latest verification: `npm run build` passes, PHPUnit passes with `55 tests, 171 assertions`, and `php artisan view:cache` passes.

Connected accounts pass on 2026-07-06:
- Accounts page was redesigned into active channel cards and a responsive active-account list.
- Workspaces can now connect multiple accounts for the same social platform.
- Fake account connection creates a new connected account row instead of overwriting by platform.
- Account display names can be supplied when creating a demo connection.
- Account disconnect is implemented as a scoped `PATCH /dashboard/accounts/{account}/disconnect` action.
- Disconnect preserves the account row, clears `access_token`, clears `connected_at`, sets `status` to `disconnected`, and writes an automation log.
- Disconnected accounts are hidden from the Accounts page and active counts, but remain in the database for history/audit and conversation safety.
- Added feature tests for multiple same-platform accounts, account-identifier routing, disconnect behavior, foreign-workspace disconnect denial, and hiding disconnected accounts.

Settings/profile/performance/UI refinement pass on 2026-07-06:
- Removed the Accounts page intro/explainer block so the page starts directly with the connected channel controls.
- Confirmed dashboard CSS has no gradients in app shell, chat wallpaper, loading indicators, tabs, cards, or social channel badges.
- Wired AI settings into behavior: auto replies, human takeover availability, and business-hours gating now affect ingestion and inbox actions.
- Added tests for disabled human takeover, disabled auto reply, and business-hours reply gating.
- Added a test ensuring AI-escalated `Needs Human` conversations stay in `ai_mode=auto` by default.
- Fixed the mode switch so icon visibility changes immediately with the optimistic UI state, and resume AI no longer sends a duplicate placeholder reply.
- Manual staff reply now flips the visible mode switch to human immediately and persists `ai_mode=human` on the conversation.
- Added mobile customer profile bottom sheet opened from the chat header name.
- Updated profile identity labels to be channel-aware: Instagram username, Facebook username, or WhatsApp number.
- Seeded demo customer external IDs with readable handles and phone numbers.
- Changed `/dashboard/inbox` so it no longer selects the first conversation by default; thread/profile data loads only after a conversation is opened.
- Hardened the dashboard SPA helper so Alpine and SPA event listeners initialize once, clicked controls show immediate pending feedback, and form submit buttons temporarily disable to prevent double submits.
- Latest verification after this pass: PHPUnit passes with `55 tests, 171 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Gmail integration pass on 2026-07-07:
- Added Gmail as the first real provider connection while preserving the existing inbox and AI Conversation State Engine architecture.
- Added `connected_accounts.refresh_token`, `token_expires_at`, and `provider_meta`.
- Added encrypted casts for Gmail access/refresh token storage.
- Added `GmailConnectionService` for OAuth URL creation, auth-code exchange, profile fetch, token refresh, manual/scheduled inbox sync, MIME body parsing, local import, and text replies through the Gmail API.
- Added Gmail dashboard routes: redirect, callback, manual sync, and reply-capable inbox integration.
- Added Gmail card to Accounts page with Connect Gmail, Sync emails, and Disconnect actions.
- Added Gmail channel metadata, icon, inbox filter support, Gmail subject preview, and email-address customer identity label.
- Gmail sync imports latest 20 inbox emails, maps conversations by Gmail thread when possible, stores subject/from/to/internal date in message metadata, and skips duplicate Gmail message IDs.
- Gmail imported messages default to `Needs Human` and `ai_mode=human`; Gmail auto-reply is intentionally disabled for now.
- Staff text replies in Gmail conversations are sent through Gmail API with reply-thread metadata when available. Gmail attachment replies remain disabled until implemented safely.
- Gmail HTML is cleaned before display, useful links are preserved, no-reply/automated senders are detected, and non-repliable email threads disable the composer.
- Added feature tests for Gmail auth requirement, callback account creation, foreign-business sync denial, scoped import, duplicate prevention, and token secrecy.
- Latest verification after this pass: PHPUnit passes with `55 tests, 171 assertions`, `php artisan view:cache` passes, and `npm run build` passes.

Telegram integration pass on 2026-07-09:
- Added Telegram as a real bot-backed provider connection, not a demo channel and not private-user MTProto inbox import.
- Accounts page now includes a Telegram card that accepts a bot username, encrypted bot token, and optional display name.
- Telegram connect creates a scoped `ConnectedAccount`, stores webhook metadata in `provider_meta`, and registers a Bot API webhook when `APP_URL` is public HTTPS.
- Local `http://127.0.0.1` / non-HTTPS development URLs cannot receive Telegram push events directly; use a public HTTPS URL or tunnel when testing real incoming messages.
- Added `POST /api/webhooks/telegram/{account}` with `X-Telegram-Bot-Api-Secret-Token` verification.
- Incoming Telegram updates are normalized into the unified inbox through `MessageIngestionService`.
- Staff text replies in Telegram conversations are sent through Telegram `sendMessage`; media replies are sent through the appropriate Telegram Bot API media methods.
- Added Telegram channel icon/filter support in the inbox and kept all channel filters on one horizontal row.
- Telegram uses bot-backed customer conversations only. It does not import a user's personal Telegram inbox or private chats.
- Telegram webhook status can show live while delivery still fails if Telegram cannot validate the site's certificate. Check `getWebhookInfo.last_error_message` when messages do not arrive.
- Latest verification after automatic Gmail watch renewal: PHPUnit passes with `92 tests, 368 assertions`, `php artisan route:list --except-vendor` passes, and `npm run build` passes.

Not built yet:
- Authorization policies/roles beyond current business-ownership checks
- Passwordless Google/magic-link authentication and Resend delivery
- Team invitation acceptance and complete Owner/Admin/Agent enforcement
- Real credit wallet/payment ledger, email-delivery history, and production alert delivery behind the designed Filament owner modules
- Advanced workspace settings and danger-zone controls
- Configurable business-hours schedule UI
- Separate immutable provider customer IDs from display usernames/phone numbers before production if real Meta payloads require both
- Real Google OAuth login/linking behavior
- Public Facebook/Instagram OAuth onboarding and production approval; development-token connections, signed webhooks, and text replies are implemented for test assets
- Provider-neutral Laravel AI agent integration with usage reporting
- Prepaid AI-credit wallet, immutable ledger, purchases, deductions, reversals, and low-balance handling
- Payment gateway integration suitable for Nigerian customers
- Removal or deliberate deprecation of legacy n8n-compatible endpoints after Laravel replacements exist
- Gmail Pub/Sub history-diff replay
- Gmail outbound attachment sending from the inbox
- Telegram private-user inbox import through MTProto
- True infinite loading for older conversations/messages
- Production error pages and observability

Direction update on 2026-07-16:
- Confirmed Nigeria-first launch under Perpetual Devs; current product name may change.
- Paused Meta test-case work pending business verification/App Review while preserving completed integration code.
- Changed monetization from a fixed subscription to a free unified inbox with prepaid paid AI usage.
- Chose Google plus email magic links as the target passwordless authentication flow.
- Added Resend, workspace roles/invitations, a private platform-owner panel, and the AI-credit ledger as the next foundation phases.
- Removed n8n from the planned production architecture; Laravel will own agent orchestration.
- Selected Gemini 2.5 Flash-Lite as the current cost-first default candidate, behind a provider-neutral interface.
- Installed Filament 4.11 for the private Perpetual Devs owner panel and enabled the required PHP `intl` extension locally.
- Added `/owner` with owner-only access, global user/workspace/conversation/connection statistics, read-only user and connection directories, workspace monitoring, and explicit suspend/reactivate actions.
- Added `users.is_platform_owner` plus `platform:owner {email}` and `platform:owner {email} --revoke` commands so access is granted explicitly instead of inferred from a customer workspace role.
- Added workspace suspension fields and enforcement in `EnsureCurrentBusiness`; suspended workspaces receive HTTP 403 while the platform-owner panel remains separate.
- Added owner-panel authorization and suspension tests. Full verification passes with `100 tests, 385 assertions`.
- Enabled Filament SPA navigation for the private owner panel.
- Expanded `/owner` into grouped operational areas for platform management, customer operations, AI operations, commercial controls, and system health.
- Added real read-only owner views for conversations, customers, AI-agent settings, and automation/error logs, including filters and record detail screens.
- Added seven-day message/conversation activity analytics and daily message/AI-enabled workspace statistics to the owner dashboard.
- Added a data-backed system-health screen for queued jobs, failed jobs, recent automation errors, expired connections, and the latest recorded failure.
- Added a pre-launch Revenue & AI Credits control screen documenting the intended NGN package, immutable wallet ledger, usage-metering, verified-payment, margin, and low-balance flow. Its monetary figures intentionally remain zero until the wallet/payment schema is implemented.
- Expanded owner-panel tests to render every new dashboard section successfully.
- Latest full verification after the owner-dashboard expansion: `100 tests, 391 assertions` pass.
- Refined the shared customer-facing dashboard shell with Filament-inspired information architecture while keeping the inbox custom: grouped sidebar navigation, workspace context, signed-in user card, sticky page header, quick inbox access, emerald active/status styling, softer cards, and consistent spacing now apply across customer pages.
- Redesigned the customer overview with a compact live-workspace welcome header, connect/inbox quick actions, operational metric tiles, recent conversations, and a visible pre-launch AI-agent credit/configuration card.
- Customer-dashboard refinement verification: production Vite build and Blade view cache pass; focused dashboard, inbox authorization, connected-account, and AI-settings tests pass with `15 tests, 56 assertions`.
