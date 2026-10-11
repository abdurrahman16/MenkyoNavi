# Email verification & learner onboarding

MenkyoNavi requires verified email addresses before dashboard, practice attempts, practice history, and Mistake Vault access. Public learning pages remain available without registration.

## Local log-mail testing

Typical local `.env`:

```env
MAIL_MAILER=log
APP_URL=http://127.0.0.1:8000
```

The log mailer does **not** deliver external email. After registration or resend:

1. Open `storage/logs/laravel.log`.
2. Find the `VerifyEmailNotification` / mail message body.
3. Copy the signed `verification.verify` URL (it uses `APP_URL`).
4. Open that URL while logged in as the same unverified user.
5. Confirm redirect to `/dashboard?verified=1`.

The verification notice UI explicitly states that no external email was delivered when `MAIL_MAILER` is `log` or `array`.

Do **not** create a public debug page that exposes signed links.

## Production SMTP prerequisites

Configure real delivery with environment variables only (never commit secrets):

- `MAIL_MAILER=smtp` (or a provider transport such as `ses` / `postmark` / `resend`)
- `MAIL_HOST`, `MAIL_PORT`
- `MAIL_USERNAME`, `MAIL_PASSWORD`
- `MAIL_SCHEME` / encryption settings as required by the provider and Laravel version
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `APP_URL` set to the real HTTPS origin used in verification links

Also complete provider-side sender authentication:

- SPF for the sending domain
- DKIM signing
- DMARC policy as appropriate

Until a real inbox delivery test is authorized and completed, do not claim Gmail/production delivery is verified.

## Verification URL requirements

- Uses Laravel temporary signed routes (`verification.verify`)
- Includes the correct user id and email hash
- Expires according to `auth.verification.expire` (default 60 minutes)
- Must be opened by the authenticated user matching the link
- Invalid/expired signatures redirect back to the verification notice with a safe localized message
- Another logged-in user cannot use someone else’s link

## Login / verification redirect behavior

| State | Protected routes (`dashboard`, attempts, mistakes, …) |
|---|---|
| Guest | Redirect to login |
| Authenticated, unverified | Redirect to `verification.notice` |
| Authenticated, verified | Allowed per normal authorization |

Registration:

1. Creates an unverified user with the current UI locale stored as `preferred_locale`
2. Logs the user in
3. Redirects to the verification notice (not the dashboard)
4. Dispatches the locale-aware verification notification

After successful verification, learners land on the dashboard with a verified banner and clear CTAs for demo practice / exam categories / learning resources. Exams are never auto-started.

## Resend behavior

- Route: `POST /email/verification-notification` (`verification.send`)
- Middleware: `auth` + `throttle:6,1`
- Already-verified users are redirected to the dashboard
- Transport failures are reported to logs and shown as a localized failure status
- Account remains unverified after failures
- Log mailer status text never claims external delivery

## Failure troubleshooting

| Symptom | Check |
|---|---|
| No inbox message locally | Expected for `MAIL_MAILER=log` — use the log link |
| Link opens 403 / invalid | Wrong user session, bad hash, or expired signature |
| Still blocked from dashboard | Confirm `email_verified_at` is set for that user |
| Production links point to localhost | Fix `APP_URL` and regenerate/resend |
| SMTP errors in UI | Credentials must never appear; check server logs only |

## Secure production configuration checklist

- [ ] `User` implements `MustVerifyEmail` (required)
- [ ] `APP_URL` is the public HTTPS origin
- [ ] External mail transport configured and authenticated
- [ ] From-address domain matches SPF/DKIM
- [ ] Auth pages remain `noindex`
- [ ] `ADSENSE_ENABLED=false` until advertising review
- [ ] No public verification bypass exists

## Remaining manual tests

- Register a fresh local user at 390px width
- Confirm verification notice copy and resend feedback
- Open the log-written signed link
- Reach dashboard with `?verified=1`
- Start Karimen demo practice after verification
- Log out / log back in
- Confirm guest access to Learn / Road Signs still works
