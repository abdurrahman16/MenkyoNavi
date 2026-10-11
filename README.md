# MenkyoNavi（免許ナビ）

Independent multilingual Japanese driving-license learning platform.

MenkyoNavi is **not** a government service, driving school, or official examination body. It provides educational practice materials and guides for learners preparing for Japanese written license exams.

## Technology

- PHP 8.4 / Laravel 13
- Blade, Livewire, Alpine.js, Tailwind CSS
- Vite for frontend assets
- PHPUnit for tests

## Main features

- Japanese-first UI with eight locales: `ja`, `en`, `zh`, `vi`, `fil`, `ne`, `bn`, `ko`
- Learning hub, study methods, pedestrian/intersection priority guides, Road Signs library
- Authentication with email verification
- Learner dashboard, Mistake Vault, exam attempt history
- Secure examination engine (server timer, one-way answer locking, scoring)
- Admin question-bank review and private Karimen preview
- SEO infrastructure (`robots.txt`, sitemap, meta, noindex for drafts)
- AdSense architecture present but disabled (`ADSENSE_ENABLED=false`)

## Content and examination status

- Public learning pages are intended for the educational MVP.
- Full public Karimen / Honmen / Gaimen exams stay unavailable until enough human-reviewed, published questions exist.
- Original Japanese Karimen candidates are staged privately for authorized human review.
- Imported third-party question banks have unresolved reuse rights and must not be published or redistributed.
- Demo practice is available only in local/testing unless explicitly enabled.

## Local development

Requirements:

- PHP 8.4 with common Laravel extensions (`pdo_sqlite` or MySQL, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`)
- Composer 2
- Node.js 20+ and npm

```bash
composer install
cp .env.example .env
php artisan key:generate
# SQLite default:
#   create empty database/database.sqlite if needed
php artisan migrate
npm install
npm run build
php artisan serve
```

Optional asset watch: `npm run dev` or `composer run dev`.

### Tests

```bash
php artisan test --compact
```

### Release checks

```bash
php artisan menkyo:seo-audit
php artisan menkyo:karimen-release-check
```

## Production notes

See `docs/MENKYONAVI_FINAL_LAUNCH.md`.

Important:

- Set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`
- Configure real SMTP before opening public registration
- Keep `ADSENSE_ENABLED=false` until a real publisher setup is approved
- Document root must be `/public`
- Do not commit `.env`, databases, or rights-restricted import assets

## License / provenance limitations

Application source is provided for the MenkyoNavi project owners.

Rights-restricted imported examination source HTML, exports, and related translation drafts are intentionally excluded from the public repository. First-party original catalogs and review tooling remain for authorized editorial workflows and are not a claim that questions are publication-approved.
