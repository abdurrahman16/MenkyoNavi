# Google Search Console readiness — MenkyoNavi

This document prepares MenkyoNavi for Search Console after a real HTTPS domain is available. It does **not** claim ownership is already verified, and it does not promise rankings, indexing volume, or traffic.

## Prerequisites

1. Deploy the application on a real HTTPS domain.
2. Set `APP_URL` to that production origin (for example `https://example.com`).
3. Leave `ADSENSE_ENABLED=false` until advertising is intentionally enabled.
4. Confirm `MENKYO_ALLOW_INDEXING` is unset (defaults to production-only indexing) or explicitly `true` only on the live site.
5. Keep staging hosts on `MENKYO_ALLOW_INDEXING=false` (or non-production `APP_ENV`) so development copies stay `noindex`.

## Ownership verification (choose one)

### HTML meta tag (supported in-app)

1. In Google Search Console, add the property for your production domain.
2. Choose the HTML-tag verification method.
3. Copy **only** the token value Google provides.
4. Set it in production environment configuration:

```env
GOOGLE_SITE_VERIFICATION=your-real-token-from-google
```

5. Deploy, then confirm the homepage `<head>` contains a single:

```html
<meta name="google-site-verification" content="your-real-token-from-google">
```

6. Complete verification in Search Console.

Do not invent or commit a fake token. Leave the variable empty until you have a real one.

### DNS or HTML file methods

DNS TXT records and Google HTML verification files are also valid. Those methods do not require `GOOGLE_SITE_VERIFICATION`. Prefer the method that matches your hosting DNS control.

## After verification — checklist

1. Deploy with the real HTTPS domain and correct `APP_URL`.
2. Verify domain ownership (meta tag, DNS, or HTML file).
3. Open `https://YOUR-DOMAIN/robots.txt` and confirm:
   - plain text (`text/plain; charset=UTF-8`)
   - private path `Disallow` rules
   - `Sitemap:` pointing at your production sitemap
4. Open `https://YOUR-DOMAIN/sitemap.xml` and confirm valid XML with absolute production URLs.
5. Submit the sitemap URL in Search Console.
6. Use URL Inspection on the homepage.
7. Inspect the three educational guides:
   - `/road-signs`
   - `/traffic-rules/pedestrian-priority`
   - `/traffic-rules/intersection-priority`
8. Confirm Search Console’s selected canonical matches the self-referencing canonical in page HTML.
9. Review excluded / `noindex` surfaces (auth, dashboard, exam attempts, privacy/terms drafts, non-Japanese guide drafts).
10. Monitor indexing coverage and crawl errors.
11. Monitor queries, impressions, and clicks once data appears.
12. Fix issues based on actual Search Console reports — do not optimize for speculative metrics.

## What the sitemap includes

Public educational hubs and approved Japanese educational guides registered in `config/menkyo.php` → `sitemap_routes`.

## What stays out of the sitemap

- Admin, login, registration, password reset
- Dashboard / profile / Mistake Vault
- Active exam attempts and personalized results
- Privacy policy and terms (public but `noindex` drafts)
- Non-indexable translation drafts (same URL, locale via cookie/session)
- Unpublished question content and private exports

## Multilingual SEO limitation (important)

MenkyoNavi currently switches language via session/cookie on **shared paths** (no `/en/...` URLs).

Therefore:

- Do **not** invent alternate language URLs in the sitemap.
- Do **not** emit `hreflang` until crawlable locale-prefixed routes and reviewed translations exist.
- Japanese remains the primary crawlable discovery path for educational guides.

### Practical next step (future task)

Introduce locale-prefixed public URLs (for example `/ja/...`, `/en/...`) for approved indexable locales, add matching `hreflang` and sitemap alternates, and keep drafts `noindex` until native review.

## Local audit

```bash
php artisan menkyo:seo-audit
php artisan menkyo:seo-audit --json
```

The command is read-only. It does not mutate the database and does not claim Google has indexed any URL.

## Related endpoints

| Endpoint | Purpose |
| --- | --- |
| `/robots.txt` | Dynamic crawl hints + sitemap pointer (`RobotsController`) |
| `/sitemap.xml` | Indexable URL inventory (`SitemapController` + `SitemapBuilder`) |

Do not recreate `public/robots.txt` — a static file would bypass the Laravel controller on many hosts.
