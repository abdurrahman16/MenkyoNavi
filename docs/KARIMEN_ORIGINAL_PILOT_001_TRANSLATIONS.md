# Original Karimen Pilot 001 — Controlled Translation Drafts (Task 46)

**Collection:** `menkyo_original_karimen_pilot_001`  
**Catalog:** `database/data/original_karimen_pilot_001_translations.php`  
**Command:** `php artisan menkyo:stage-original-karimen-pilot-translations {--dry-run|--commit}`  
**Service:** `App\Services\Editorial\OriginalKarimenPilotTranslationDraftService`

## Status statement

These are **private, AI-assisted translation drafts**. They are **not** publication-ready and are **not** native-speaker verified.

Research assessment `supported_as_written` enables drafting only. It does **not** mean human accuracy approval, editorial approval, rights clearance, answer-key verification, or publication approval.

Inspected before Task 46 drafting: **0** `human_accuracy_review_decision` audit rows in the development database.

## Eligible Japanese candidates (8)

| Candidate ID | Topic (JA) | Research assessment |
| --- | --- | --- |
| `menkyo_original_karimen_pilot_001:2` | 一時停止 | supported_as_written |
| `menkyo_original_karimen_pilot_001:3` | 横断歩道と歩行者 | supported_as_written |
| `menkyo_original_karimen_pilot_001:4` | 踏切の通過 | supported_as_written |
| `menkyo_original_karimen_pilot_001:5` | 踏切の警報・遮断 | supported_as_written |
| `menkyo_original_karimen_pilot_001:6` | 最高速度 | supported_as_written |
| `menkyo_original_karimen_pilot_001:7` | 安全運転義務 | supported_as_written |
| `menkyo_original_karimen_pilot_001:9` | 右折時の進行妨害禁止 | supported_as_written |
| `menkyo_original_karimen_pilot_001:10` | 一時停止と徐行の混同 | supported_as_written |

Eligibility also requires a staged Japanese source that is stable (present JA text, not rejected/deferred/revision-requested, not fingerprint-stale).

## Withheld candidates (2)

| Candidate ID | Reason |
| --- | --- |
| `:1` | `supported_with_clarification` — Art. 7 subject-scope clarification unresolved until a human applies the Task 47 source revision |
| `:8` | `supported_with_clarification` — proposed Art. 36(1)(i) streetcar-aware stem not applied until a human applies the Task 47 source revision |

Do not translate the proposed `:8` stem as if it already replaced the current Japanese statement.

After an authorized reviewer **applies** a Task 47 source revision, `OriginalKarimenPilotTranslationDraftService` can treat that candidate as newly draft-eligible for the revised fingerprint. Task 47 does **not** auto-generate the remaining 14 drafts.

## Target languages (7)

`en`, `zh`, `vi`, `fil`, `ne`, `bn`, `ko`

Japanese (`ja`) remains the authoritative source language and is not overwritten by this command.

**Maximum drafts:** 8 × 7 = **56** private translation rows.

## Drafting method

`ai_assisted_controlled_draft_task_46`

Each draft stores:

- Candidate ID
- Source Japanese content fingerprint (Task 45 `reviewContentFingerprint`)
- Target locale
- Translated statement and explanation
- Drafting method
- `native_reviewed=false`
- `reviewed_at=null`
- Status `draft` or `needs_review` (quality flags only; never auto `reviewed`)

## Source-version handling

If Japanese stem, answer, explanation, or material legal-reference fields change, the stored fingerprint no longer matches. Affected translations are flagged `source_version_mismatch` / `source_version_stale` and cannot become publication-eligible while stale. Historical text is preserved; human-reviewed rows are not silently overwritten.

## Terminology notes

A small private terminology map in the catalog covers recurring concepts (一時停止, 徐行, 横断歩道, 踏切, 優先道路, 最高速度, 安全運転義務, 交差点, 道路標識). Target-language wording keeps natural equivalents and often retains the Japanese term for learner recognition. Statutory references remain Japanese Road Traffic Act provisions; they are not rewritten as foreign local laws.

## Draft counts (target)

| Locale | Drafts |
| --- | ---: |
| en | 8 |
| zh | 8 |
| vi | 8 |
| fil | 8 |
| ne | 8 |
| bn | 8 |
| ko | 8 |
| **Total** | **56** |

`:1` and `:8` contribute **0** drafts while withheld.

## Native-review requirements

Before any publication path:

1. Native or qualified bilingual review per locale
2. Confirm legal qualifications and negation polarity
3. Confirm numbers, vehicle categories, and exceptions survived
4. Confirm source fingerprint still matches
5. Record an actual reviewer identity through authorized editorial workflow (not invented here)

## Approval dependencies (independent)

Creating drafts must not change:

- Japanese human accuracy review
- Japanese editorial approval
- Answer-key verification
- Provenance / rights clearance
- Translation native review
- Publication (`is_published`)
- Live `Question` promotion / exam-pool eligibility

## Remaining legal uncertainties

- `:1` scope (歩行者等 vs 車両等 framing)
- `:8` whether to adopt the proposed streetcar-aware stem under Art. 36(1)(i)
- No claim that first-party content is cleared for commercial reuse
- Automated quality flags are preliminary only

## Admin surfaces

- Pilot accuracy review index shows withheld translation reasons
- Question-bank show page surfaces fingerprint, drafting method, native-review=false, and stale warnings for pilot entries

## Staging command

```bash
php artisan menkyo:stage-original-karimen-pilot-translations --dry-run
php artisan menkyo:stage-original-karimen-pilot-translations --commit
```

Default without flags is dry-run. Disabled in production. Idempotent; skips human-protected reviewed translations.
