# Learner Journey QA — Task 37

Date: 2026-10-11  
Environment: local (`APP_URL=http://127.0.0.1:8000`)  
Scope: functional QA for the homepage → learn → exam → results loop, plus auth, contact/SEO/AdSense safeguards.

## Workflow tested

Primary journey covered by automated regression (`LearnerJourneyQaTest` + existing exam suite):

1. Homepage learning CTAs  
2. Learn hub (roadmap, browser-local checklist, practice CTA)  
3. Exam landings (Karimen / Honmen / Gaimen Kirikae)  
4. Demo practice start (auth required)  
5. One-way answer lock + auto-advance (Livewire)  
6. Submit modal open/cancel/confirm  
7. Results + full review  
8. Return to Road Signs / Learn  
9. Mistake Vault isolation (demo does not populate vault)

Supporting surfaces: dashboard/history isolation, contact/SEO routes, AdSense-disabled surfaces, eight-locale copy keys for exam/checklist strings.

## Automated test coverage

| Area | Coverage |
|---|---|
| Public learn path | `LearnerJourneyQaTest`, `LearnerExperienceTest`, `PublicPagesTest` |
| Auth / verified gate | `LearnerJourneyQaTest`, `DashboardAccessTest`, `EmailVerificationTest` |
| Exam start / demo vs full | `StartExamAttemptTest`, `ExamLandingPracticePanelTest` |
| Lock / advance / shuffle | `OneWayExamFlowTest`, `ExamAnsweringTest`, `ExamQuestionShuffleTest` |
| Livewire submit + timeout poll | `LearnerJourneyQaTest` (new) |
| HTTP timeout notice i18n | `LearnerJourneyQaTest` (new) |
| Results / review / vault | `ExamResultsTest`, `MistakeVaultTest` |
| Account isolation | Route-bound 404 + policy checks |
| Road Signs structure | `RoadSignsGuideTest` |
| Contact / SEO / ads | `ContactFormTest`, `TechnicalSeoTest`, `AdSlotDisabledTest` |

Commands run:

- `php artisan test --compact` → **439 passed / 6,678 assertions**
- `npm run build` → success
- `php artisan menkyo:seo-audit` → read-only OK (local APP_URL warning expected)

## Actual defects found

1. **HTTP exam submit timeout notice was hard-coded English**  
   `ExamAttemptController::submit` used a literal English string while Livewire used `__('exams.time_expired')`.

2. **`verified` middleware was ineffective**  
   Routes under `auth` + `verified` did not enforce verification because `User` did not implement `MustVerifyEmail` (interface was commented out). Unverified users could open dashboard / attempts / Mistake Vault.

## Corrections made

1. Localized timeout flash in `ExamAttemptController::submit` via `__('exams.time_expired')`.  
2. Restored `User implements MustVerifyEmail` so the existing `verified` middleware matches product intent and verification tests.  
3. Marked learn-hub checklist note with `data-study-checklist-local-note` for regression visibility (copy already disclosed browser-only storage).  
4. Added `tests/Feature/LearnerJourneyQaTest.php` covering the end-to-end demo journey, Livewire timeout paths, HTTP timeout i18n, ownership isolation, resume shuffle stability, checklist/local-storage honesty, AdSense-off surfaces, and eight-locale copy keys.

## Outstanding manual checks

These were **not** fully browser-exercised in Task 37 (no Dusk suite). Confirm locally before production:

- Real device/browser at 390 / 768 / 1440 for exam True/False touch targets and submit modal fit  
- Multi-tab race while answering (automated lock coverage exists; visual UX not observed)  
- Honmen illustrated group confirm UX with keyboard-only navigation  
- Language switch mid-exam UI labels in each of the eight locales  
- Contact form honeypot/rate-limit behavior under real browser conditions  
- Logout clearing of session locale preference (expected product behavior may vary)

## Known content restrictions

- Imported Karimen bank remains unpublished / not publication-ready (0/50 for first imported set)  
- Full practice stays unavailable without sufficient reviewed published questions  
- Non-Japanese educational guides remain draft + `noindex`  
- Demo practice must not claim official exam readiness  
- Checklist progress is browser `localStorage` only — never account progress  

## Remaining deployment requirements

- Set production `APP_URL` (HTTPS) before Search Console  
- Keep `ADSENSE_ENABLED=false` until inventory + policy review  
- Configure mailer for real verification/contact delivery (local log mailer only in QA)  
- Complete human copyright / accuracy / figure reviews before publishing any imported questions  
- Optional: native-speaker review before indexing translated guides  

## Honest readiness note

Local automated green builds prove server-side contracts for the learner journey. They do **not** alone prove production readiness, AdSense compliance, or content publication readiness.
