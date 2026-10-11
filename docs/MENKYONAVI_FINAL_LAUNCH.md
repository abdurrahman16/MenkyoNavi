# MenkyoNavi — Final Launch Handoff

## 1. GitHub repository

- URL: https://github.com/abdurrahman16/MenkyoNavi
- Visibility note: repository is **public** — rights-restricted import assets stay local-only
- Local project directory: `F:\PRIOS\MenkyoNavi`
- Branch used for upload: `main`
- Final commit hash: *(filled after successful push)*

## 2. Validation snapshot

Last verified locally (2026-10-11):

- `php artisan test --compact` → **617 passed**, **12,261 assertions**
- `npm run build` → success
- `php artisan menkyo:seo-audit` → OK; warning: local `APP_URL` (expected until production domain)
- `php artisan menkyo:karimen-release-check` → public Japanese Karimen **no** (0/50 eligible)

## 3. Security / content exclusions from Git

Ignored / not uploaded:

- `.env` and real secrets
- `database/*.sqlite*` and SQL dumps
- `vendor/`, `node_modules/`, `public/build` (rebuild on deploy)
- `storage/app/menkyo-imports/` (imported HTML sources)
- `storage/app/menkyo-exports/` (private audit/export payloads)
- `database/data/karimen_test_1_*` (imported-bank translation drafts)
- `database/data/pilot_staged_translations.php`
- Runtime logs, caches, sessions

Safe placeholders remain in `.env.example`.

## 4. Hosting requirements

- PHP 8.4+ with Laravel extensions
- Composer 2
- Node 20+ for `npm ci && npm run build` (or CI artifact)
- MySQL 8+ recommended for production (SQLite OK for local)
- HTTPS reverse proxy to Laravel `public/` document root
- Writable `storage/` and `bootstrap/cache/`
- Cron: `* * * * * php /path/to/artisan schedule:run`
- Queue worker if `QUEUE_CONNECTION` is not `sync` (default example uses `database`)

## 5. Production `.env` checklist

```env
APP_NAME=MenkyoNavi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
APP_KEY=base64:...   # php artisan key:generate on the server; never commit
APP_LOCALE=ja

SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*    # or explicit proxy IPs

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=menkyo_navi
DB_USERNAME=...
DB_PASSWORD=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@your-verified-domain.example
MAIL_FROM_NAME="${APP_NAME}"

# Leave unset to auto-open registration only when mail delivers externally
# REGISTRATION_ENABLED=

ADSENSE_ENABLED=false
CONTACT_RECIPIENT_EMAIL=owner-private@example.com
# MENKYO_ALLOW_INDEXING=   # empty follows production indexing policy
# GOOGLE_SITE_VERIFICATION=
```

Commands after deploy:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. SMTP configuration

Until authenticated SMTP (or SES/Postmark/Resend) is configured:

- Verification and password-reset mail will not reach learners
- Public registration auto-closes outside local/testing when the mailer is `log`/`array`
- Contact form delivery also requires a working mailer + `CONTACT_RECIPIENT_EMAIL`

Private smoke test after SMTP setup:

```bash
php artisan menkyo:verify-mail-delivery
```

## 7. Remaining content approval requirements

### Public educational MVP

- Confirm factual accuracy / rights of public guides and any demo samples shown publicly
- Confirm Privacy Policy and Terms details (currently public drafts, noindex)
- Supply production domain + HTTPS
- Configure SMTP or keep registration closed
- Smoke-test public pages on the real domain

### Full public Japanese Karimen

1. Resolve source revisions `:1`, `:8`, `:26`, `:34`, `:49`
2. Human accuracy / answer-key / editorial / rights for all 50
3. Mark Japanese ready and publish through admin
4. `php artisan menkyo:karimen-release-check` until Japanese public Karimen = yes

Do not fabricate approvals.

## 8. Final launch blockers

- No production domain configured in this workspace
- SMTP not production-ready (`MAIL_MAILER=log` locally)
- Public Karimen: 0/50 human-approved published questions
- Privacy/Terms need owner confirmation before claiming legal completeness
- AdSense remains disabled by design

## 9. Rollback

1. Redeploy previous known-good commit
2. Restore DB backup taken before migrate
3. `php artisan config:clear && php artisan cache:clear`
4. Confirm `APP_DEBUG=false` and HTTPS still enforced
