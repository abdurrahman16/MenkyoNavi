# Karimen 50 — Exam Activation (Task 50)

## 1. Start the private 50-question examination

1. Sign in as a verified administrator (`php artisan menkyo:grant-admin you@example.com`).
2. Open `/admin/questions/original-karimen-exam-preview`.
3. Confirm the banner **管理者用・未承認問題の試験プレビュー**.
4. Click **Start private 50-question preview** (or resume/restart).
5. You are taken into the existing exam runner with all 50 original Japanese candidates, timer, True/False locking, auto-advance, submit confirmation, scoring, and incorrect/unanswered review.

Also linked from the question-bank dashboard and the accuracy-review index.

## 2. Private preview vs public practice

| | Private admin preview | Public full Karimen |
|---|---|---|
| Access | Verified administrators only | Authenticated learners when pool is eligible |
| Source | Staged original Japanese 50 | Published + content-reviewed live questions |
| Answers | Provisional proposed keys | Verified published keys |
| Analytics / Mistake Vault / history | Excluded (`admin_preview`, `counts_toward_analytics=false`) | Included for full attempts |
| Public availability | Never implied | Only when pool eligibility passes |

## 3. Review the 50 source candidates

Use `/admin/questions/original-karimen-pilot` (filter by collection / pending / needs clarification).

Each candidate show page has previous/next navigation in candidate order (1→50), evidence, proposed answer, explanation, legal source, accuracy decision, and revision history.

## 4. Resolve the five clarification questions

Candidates `:1`, `:8`, `:26`, `:34`, `:49` keep research proposals on the same show page:

1. Review current Japanese wording  
2. Read proposed wording  
3. Open legal evidence  
4. Apply or reject the revision  
5. Record a rationale  
6. Re-review the resulting version for accuracy  

Applying a revision is **not** accuracy approval. Content fingerprint changes invalidate prior accuracy decisions and mark translations stale.

## 5. Release-readiness command

```bash
php artisan menkyo:karimen-release-check
php artisan menkyo:karimen-release-check --json
```

Read-only. Reports staged counts, research/human/answer-key/editorial/rights/published gates, Japanese live-pool eligibility, locale availability, Honmen/Gaimen shortages, and blocking candidate IDs. Does not mutate records.

## 6. Required human approvals (per question)

Before a question can enter the public pool:

- Human accuracy accepted for the **current** content fingerprint  
- Answer key verified with evidence  
- Human editorial approval  
- Rights/provenance cleared for public reuse  
- Japanese translation ready for publication  
- No unresolved source-revision proposal  
- Staging publication blockers empty  

Research `supported_as_written` is **not** human approval.

## 7. Publishing an eligible question

On `/admin/questions/{entry}`, use **Publish to live exam pool** after gates pass. The action:

- Re-checks gates server-side  
- Promotes to a draft Question, then marks it Published + content-reviewed  
- Attaches the Karimen exam type  
- Writes an audit row  
- Refuses safely when any gate fails  

No bulk approve/publish button.

## 8. Japanese-only public availability

Public full Karimen for locale `ja` activates automatically once 50 eligible published questions exist in the live pool. Other locales are evaluated separately and do not block Japanese.

## 9. What still blocks other languages

Locales `en`, `zh`, `vi`, `fil`, `ne`, `bn`, `ko` need 50 eligible **reviewed** translations each. Current drafts remain private machine-assisted drafts and must not be treated as verified. The system never mixes Japanese stems into a non-Japanese full exam.

## 10. What still blocks Honmen and Gaimen

- **Honmen:** needs 90 standard + 5 illustrated three-statement groups (published/reviewed). Imported ordinary items are not illustrated groups.  
- **Gaimen:** needs 50 published/reviewed standard items for that exam type.  
- The 1,024 imported staged questions remain private with unresolved rights.

## 11. Shortest remaining manual release checklist

1. Resolve clarifications `:1 :8 :26 :34 :49` (revision apply/reject + notes).  
2. Human accuracy-accept all 50 current versions.  
3. Verify answer keys + clear rights + grant editorial approval per question.  
4. Mark Japanese translations ready for publication.  
5. Publish each eligible question individually to the live pool.  
6. Re-run `php artisan menkyo:karimen-release-check` until Japanese public Karimen reports available.  
7. Optionally continue native translation review for the other seven locales.

Private preview can be used at any time before steps 2–6 without affecting public eligibility.
