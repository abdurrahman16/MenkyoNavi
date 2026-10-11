# MenkyoNavi — Production Deployment Checklist

Provider-neutral checklist. Do not deploy until hosting, DNS, and secrets are ready.
Do **not** enable live AdSense or send external mail without real credentials and explicit authorization.

## 1. Domain and hosting

- Point the public domain to a host that can run PHP 8.4+, Composer, and MySQL 8+.
- Set the web server document root to the `public/` directory only (never the repo root).
- Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process and not publicly browsable.
- Prefer HTTPS termination at the load balancer or web server.

## 2. PHP extensions and Composer

Required (typical Laravel set): `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `json`.

```bash
composer install --no-dev --optimize-autoloader
```

## 3. MySQL configuration

1. Create an empty utf8mb4 database (example name: `menkyo_navi`).
2. Create a dedicated database user with privileges on that database only.
3. Set `DB_CONNECTION=mysql` and related credentials in the server environment / `.env`.
4. Do **not** change a working local SQLite development database as part of this checklist unless you intentionally migrate data.

## 4. Environment variables

Copy `.env.example` → production secrets store / `.env`, then set at minimum:

| Variable | Production expectation |
|----------|------------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain.example` |
| `APP_KEY` | Generated once; never regenerate casually |
| `APP_LOCALE` | `ja` |
| `SESSION_SECURE_COOKIE` | `true` |
| `TRUSTED_PROXIES` | `*` or explicit proxy IPs when behind a balancer |
| `CONTACT_RECIPIENT_EMAIL` | Private inbox (server-only) |
| `MAIL_*` | Real provider transport + verified From |
| `ADSENSE_ENABLED` | Keep `false` until authorized |

Never commit populated `.env` files or paste secrets into tickets/chat.

## 5. Application key

```bash
php artisan key:generate --force
```

Run only on first provision (or when intentionally rotating). Back up the previous key if encrypted data exists.

## 6. Database migration and backups

Before migrate:

```bash
# Host-specific backup of the target database first
php artisan migrate --force
```

- Prefer `migrate` over `migrate:fresh` / `migrate:refresh` in production.
- Keep staged examination questions unpublished until editorial release.
- Schedule regular MySQL dumps (host cron or managed backup).

## 7. Frontend assets

Build on CI or the deploy host with Node.js available:

```bash
npm ci
npm run build
```

Deploy `public/build/` with the release. Remove any `public/hot` Vite marker from production.

## 8. HTTPS setup

- Obtain a TLS certificate for the public hostname.
- Redirect HTTP → HTTPS at the edge.
- Set `APP_URL` to the HTTPS origin.
- Set `SESSION_SECURE_COOKIE=true`.
- When TLS terminates at a proxy, set `TRUSTED_PROXIES` so Laravel sees HTTPS correctly.
- Production forces HTTPS URL generation in application code.

## 9. Mail provider setup

1. Choose SMTP, Amazon SES, Postmark, or Resend.
2. Verify the sending domain (SPF / DKIM / DMARC as required by the provider).
3. Set `MAIL_MAILER`, host/credentials, and `MAIL_FROM_ADDRESS` to that verified address.
4. Set `CONTACT_RECIPIENT_EMAIL` (never expose in HTML).
5. Confirm the contact form Reply-To uses the visitor email (application behavior).
6. Safe delivery check after credentials exist:

```bash
php artisan menkyo:verify-mail-delivery
# production confirmation prompt; add --force only when intentional
```

- `MAIL_MAILER=log` must not be treated as external delivery.
- Do not send unsolicited test mail to third parties.

## 10. AdSense publisher ID configuration

1. Obtain a real publisher ID from Google AdSense (never invent placeholders).
2. Optionally set `ADSENSE_PUBLISHER_ID=pub-…` so `/ads.txt` becomes available for verification **while** `ADSENSE_ENABLED=false`.
3. Keep `ADSENSE_ENABLED=false` until privacy/CMP readiness and explicit go-live authorization.
4. Manual unit IDs (`ADSENSE_SLOT_*`) stay empty until units exist.
5. Exam-adjacent slots remain disabled in config by default.

## 11. Consent-management configuration

- No Google-certified CMP is bundled with this repository.
- With `ADSENSE_REQUIRE_CONSENT=true` (default), live ads stay off until `ADSENSE_CONSENT_GRANTED` is set by a real CMP integration.
- Before enabling ads in EEA/UK/Switzerland (and other required regions), integrate a Google-certified CMP and update Privacy / Cookie / Advertising pages.
- A custom dialog is **not** a certified CMP.

## 12. ads.txt verification

1. Set a validated `ADSENSE_PUBLISHER_ID`.
2. Confirm `GET https://your-domain.example/ads.txt` returns a single `google.com, pub-…, DIRECT, f08c47fec0942fa0` line.
3. Complete AdSense site verification.
4. Only afterward consider setting `ADSENSE_ENABLED=true` (still subject to consent).

Until a real publisher ID is configured, `/ads.txt` returns 404 by design.

## 13. Scheduled jobs and queue workers

Current public contact mail is sent synchronously (no queue worker required for contact alone).

If you keep `QUEUE_CONNECTION=database` (or Redis) and dispatch queued jobs later:

- Run a queue worker under process supervision (`php artisan queue:work`).
- Add a scheduler entry if you introduce scheduled Artisan tasks: `* * * * * php artisan schedule:run`.

Today’s core public pages do not require a cron for basic browsing and contact delivery.

## 14. Rollback and backup procedure

1. Keep the previous release directory / container image.
2. Back up the MySQL database before each migrate.
3. To roll back application code: redeploy the previous release artifact and restore `APP_KEY` / `.env` unchanged.
4. To roll back a bad migration: restore the DB dump taken immediately before migrate, then redeploy matching code.
5. After rollback: `php artisan config:cache` / `route:cache` / `view:cache` as used by your host, then smoke-test home, contact, login, and one exam landing page.

## Post-deploy smoke checks

- `APP_DEBUG=false` and no stack traces on public errors.
- Contact form submits without exposing the recipient address in HTML.
- `/robots.txt` disallows `/dashboard`, `/admin`, auth paths; lists sitemap.
- `/sitemap.xml` loads.
- `/ads.txt` is 404 (no publisher) or valid (publisher set) — never a fake ID.
- AdSense scripts absent while `ADSENSE_ENABLED=false`.
- Admin routes require authenticated administrator accounts.
- Imported HTML / translation exports under `storage/app/` are not downloadable via public URLs.

## Related local verification (non-production)

```bash
php artisan test --compact
npm run build
php artisan menkyo:verify-mail-delivery   # only after real mail config; log driver warns honestly
```
