# Original Karimen pilot — human accuracy review workflow

Collection: `menkyo_original_karimen_pilot_001`  
Admin routes:

- Index: `/admin/questions/original-karimen-pilot`
- Candidate: `/admin/questions/original-karimen-pilot/{entry}`

This workbench records **human accuracy decisions only**. It does not publish questions, clear rights, grant editorial approval, verify answer keys, generate translations, or promote staged rows into live `Question` records.

## Reviewer authorization

- Middleware: `auth`, `verified`, `admin`
- Policy: `QuestionImportEntryPolicy::reviewAccuracy` (verified administrators)
- Guests, ordinary learners, and unverified accounts cannot open or submit reviews
- Reviewer identity is always the authenticated user ID (never a free-text name field)
- CSRF and Livewire request protection remain active
- Rate limiting applies per reviewer + candidate

Do not create a public registration path for reviewers. Do not auto-grant admin privileges.

## Decision definitions

| Decision | Meaning |
| --- | --- |
| Accuracy accepted | An authorized human accepted the **current content version** for legal/answer accuracy |
| Revision requested | Content or framing needs a controlled revision; wording is **not** auto-changed |
| Rejected for accuracy | Current version is not acceptable for accuracy |
| Deferred / evidence needed | More evidence or clarification is required before acceptance |

Accuracy acceptance does **not** mean:

- Final editorial approval
- Rights clearance
- Answer-key verification complete
- Native translation approval
- Legal-professional sign-off beyond the reviewer’s own judgement
- Publication or exam-pool eligibility

Research assessments (`supported_as_written`, `supported_with_clarification`, …) are never treated as human approval.

## Evidence expectations

Reviewers should compare:

- Japanese stem, proposed true/false answer, Japanese explanation
- Exact provision / article reference and source URL
- Evidence summary, exceptions, effective-date concerns, ambiguity notes
- For acceptance: an intentional confirmation checkbox plus a concise evidence-based rationale (notes ≥ 12 characters)
- For revision / reject / defer: meaningful notes explaining the judgement

No decision is pre-selected. There is no bulk “approve all 10” action.

## Candidate :1 handling

Scope concern: **Road Traffic Act Art. 7 subject scope**（歩行者等又は車両等）. This is **not** an Art. 38 crosswalk item (`:3` already covers that). The staged stem is vehicle-focused.

Task 47 adds a controlled **source revision** panel with a research-supported stem aligning subjects to Art. 7. Accuracy acceptance still requires the scope acknowledgement. Applying a source revision is a separate action and does **not** grant accuracy approval.

## Candidate :8 handling

Article 36(1)(i) intersection priority and streetcar-related conditions.

Task 47 surfaces side-by-side current vs proposed stems, statute evidence, and explicit revision decisions (apply / request changes / reject / defer). Proposals are **not** auto-applied. A material Apply updates Japanese text, recalculates the Task 45 fingerprint, retains previous wording in history, and invalidates prior accuracy acceptance for the new version.

## Version / revision safety

Each decision stores a SHA-256 content fingerprint over:

- Stable candidate ID
- Japanese question text
- Correct answer
- Japanese explanation
- Answer-key reference
- Exact provision metadata

Submissions with a stale fingerprint are rejected; the reviewer must reload.

If material reviewed content changes after acceptance, the application demotes the acceptance for the current version (`human_accuracy_approval_stale`) while preserving historical audit rows.

## Audit history

Each successful decision writes `QuestionImportReviewAudit` with action `human_accuracy_review_decision`, including:

- Candidate ID
- Authenticated reviewer user ID
- Decision
- Notes
- Timestamp
- Reviewed content fingerprint
- Previous / new accuracy status fields
- Explicit flags that editorial, rights, publication, and answer-key gates were **not** conferred

If no human has reviewed the candidate, the UI shows **未審査**.

## Approval separation

Independent gates remain independent:

- Human accuracy
- Editorial approval
- Content rights
- Answer-key verification
- Publication (`is_published`)
- Promotion (`promoted_question_id`)

Accuracy acceptance alone must leave the other gates closed.

## Publication safeguards

All ten pilot candidates remain private staging rows for `original_karimen_pilot` / `menkyo_original_karimen_pilot_001`. They must stay out of:

- Full Karimen / Honmen / Gaimen / Demo pools
- Public learning quizzes, search, sitemap, Mistake Vault

Imported source-bank questions (1,024 English imports) are out of scope for this endpoint.

## Manual review steps (authorized human only)

1. Log in to the existing admin account (verified administrator).
2. Open **Original Karimen pilot accuracy review** from the question-bank dashboard.
3. Select a pending candidate (filter: 未審査 / 要対応).
4. Compare the Japanese stem and answer with the cited statute/source.
5. Read ambiguity notes; for :1 and :8, read the special panels in full.
6. Choose an accuracy decision (none is selected by default).
7. Enter a concrete rationale (12+ characters). For acceptance, tick the confirmation (and :1/:8 acknowledgements when shown).
8. Confirm the browser dialog, then submit.
9. Verify the history panel updated and still shows your account identity.
10. Confirm `公開` remains `false` and no live-pool promotion occurred.

Automation and research audits must **not** perform these decisions on behalf of a human reviewer.
