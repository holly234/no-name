# Pre-launch Engineering Audit

Last updated: 2026-07-20

## Scope

Security, workspace isolation, webhook ingress, queues, AI recovery, file handling, database growth, and production configuration were reviewed. Meta production onboarding, Resend delivery, and payments remain intentionally on hold.

## Fixed in this audit

- Provider tokens and metadata are hidden from model serialization.
- Fake account creation and legacy/n8n compatibility endpoints are disabled by default.
- Provider webhooks use a burst-friendly limiter; Telegram authenticates before any database write.
- Empty Meta verification tokens are rejected.
- Attachments use a safe upload allowlist, unsafe types cannot render inline, and responses use `nosniff`.
- Analytics uses the actual `incoming` direction.
- Team invitations expire after seven days.
- AI recovery stops after three attempts per message.
- Recovery, analytics, and AI-attempt queries gained composite indexes.

## Verified existing controls

- Dashboard routes require authentication, a current workspace, and appropriate roles.
- Inbox, attachments, team actions, settings, and knowledge records validate workspace ownership.
- OAuth tokens are encrypted at rest and webhook requests are authenticated.
- Queue workloads are separated into webhook, AI, sync/mail, and default queues.

## Remaining before public production

1. Centralize provider-error redaction so external API errors cannot expose credentials in logs or UI.
2. Paginate large knowledge/team collections and consolidate AI-credit aggregate queries.
3. Define retention/pruning for messages, logs, failed jobs, and attachments.
4. Add uptime/error monitoring, queue-depth alerts, and tested database backups.
5. Prefer authenticated Google Pub/Sub push/OIDC over query-token verification.
6. Complete domain-dependent Meta/email work and payments when those holds are lifted.

## Final backend hardening update

- External provider exception messages are redacted before being written to logs, operational metadata, or customer-facing errors.
- Exhausted queue jobs emit a credential-safe critical log event and appear in System Health alongside stale reserved jobs and disconnected/expired accounts.
- Daily retention removes routine automation logs after 30 days, error logs after 90 days, expired invitations after their retention window, failed jobs after 30 days, and old job batches after seven days. Customer conversations and attachments are not pruned automatically.
- Knowledge-base and team collections are paginated, while monthly AI-credit statistics use one aggregate query.

## Production requirements

Keep `FAKE_CONNECTIONS_ENABLED`, `LEGACY_WEBHOOK_ENDPOINTS_ENABLED`, and `N8N_COMPATIBILITY_ENDPOINTS_ENABLED` false. Also use `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS, and unique uncommitted secrets.
