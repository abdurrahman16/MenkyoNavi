# Original Karimen Pilot 001 — Source Revision Proposals (Task 47)

**Collection:** `menkyo_original_karimen_pilot_001`  
**Machine-readable catalog:** `database/data/original_karimen_pilot_001_source_revisions.php`  
**Service:** `App\Services\Editorial\OriginalKarimenPilotSourceRevisionService`  
**Related:** Task 44 accuracy audit · Task 45 human accuracy workbench · Task 46 translation drafts

**Status:** Research-supported proposals prepared. **0** human source-revision decisions recorded by this task. Proposals are **not** applied to live staged Japanese text until an authorized reviewer acts.

**Sources accessed:** 2026-10-11 — [道路交通法（e-Gov）](https://laws.e-gov.go.jp/law/335AC0000000105/)

---

## Candidate :1

### Existing issue

Task 46 withheld `:1` under a “pedestrian / Art. 7 subject-scope” label. The live candidate is **not** an Article 38 crosswalk item (`:3` already covers Art. 38 and is `supported_as_written`). The open issue is that Art. 7’s duty subjects are **歩行者等又は車両等**, while the current stem names only **車両等**.

### Applicable statutory evidence

Inspected e-Gov Art. 7 (access 2026-10-11):

> 道路を通行する歩行者等又は車両等は、信号機の表示する信号又は警察官等の手信号等（前条第一項後段の場合においては、当該手信号等）に従わなければならない。

No material effective-date conflict identified for this proposition. Unsigned-road ordinance speed questions remain excluded.

### Exact current statement

- **Question:** 道路を通行する車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。
- **Answer:** 正しい（True）
- **Explanation:** Notes that Art. 7 also covers 歩行者等; vehicle duty remains accurate.

### Proposed corrected statement

- **Question:** 道路を通行する歩行者等又は車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。
- **Answer after revision:** 正しい（unchanged）
- **Explanation:** 道路交通法第7条は、道路を通行する歩行者等又は車両等が信号機の表示する信号又は警察官等の手信号等に従う義務を定めています。設問はこの法定義務の主体と内容を問うものであり、信号に従わない進行は許されません。

### Why this improves accuracy

Aligns the stem subjects with the statute so learners cannot misread the duty as vehicle-only.

### Remaining questions

- Hand-signal priority (Art. 6(1) latter part) stays out of scope.
- Signal-color meanings stay out of scope.

### Human revision decision status

`pending_human_decision` — **not** auto-applied; **not** human accuracy-approved.

---

## Candidate :8

### Existing issue

Art. 36(1)(i) forbids a **vehicle** from impeding (a) vehicles approaching from the left on the intersecting road **and** (b) **streetcars** traveling on the intersecting road, except where Art. 36(2) applies. The current stem centers on left-side **車両等** and omits the streetcar clause; the subject is over-generalized as 車両等.

### Article 36 evidence

Inspected e-Gov Art. 36 (access 2026-10-11):

- Scene: 交通整理の行なわれていない交差点
- Carve-out: 次項（第2項）の規定が適用される場合を除き
- (1)(i) vehicle case: 交差道路を左方から進行してくる車両 **及び** 交差道路を通行する路面電車
- (1)(ii) streetcar-as-subject: left-approaching streetcars only (out of this item’s teaching focus)
- (2): priority road / clearly wider intersecting road → must not impede vehicles/etc. on that intersecting road

This is **not** a universal “always yield to the left” rule.

### Streetcar-related qualification

Streetcars on the intersecting road are part of the vehicle-subject duty in Art. 36(1)(i). Ordinary vehicles and streetcars are **not** treated identically when the actor is a streetcar (Art. 36(1)(ii)).

### Exact current statement

- **Question:** 交通整理の行われていない交差点においては、道路交通法第36条第2項が適用される場合を除き、交差道路を左方から進行してくる車両等の進行妨害をしてはならない。
- **Answer:** 正しい（True）
- **Explanation:** Mentions streetcars and Art. 36(2); stem still omits streetcars.

### Proposed corrected statement

- **Question:** 交通整理の行われていない交差点においては、道路交通法第36条第2項が適用される場合を除き、車両は、交差道路を左方から進行してくる車両及び交差道路を通行する路面電車の進行妨害をしてはならない。
- **Answer after revision:** 正しい（unchanged）
- **Explanation:** Clarifies vehicle-subject Art. 36(1)(i), keeps Art. 36(2) carve-out, and notes streetcar-as-subject (1)(ii) is out of scope.

### Why this improves accuracy

Narrows the actor to 車両, adds the statutory streetcar clause, and retains the priority/wider-road exception so the True/False proposition matches Art. 36(1)(i)–(2).

### Remaining questions

- Roundabouts (Art. 37-2) are separate.
- Signalized intersections are outside this scene.

### Human revision decision status

`pending_human_decision` — **not** auto-applied; **not** human accuracy-approved. Replacement as a new candidate is **not** required if the proposal is accepted.

---

## Workflow

### How a reviewer evaluates the proposal

1. Sign in as a verified administrator.
2. Open `/admin/questions/original-karimen-pilot/{entry}` for `:1` or `:8`.
3. Read the unresolved-source warning, current Japanese text, proposed revision, statute excerpt, and remaining caveats.
4. Compare with the e-Gov link shown on the page.

### How a revision is applied

1. In **ソース改訂の判定**, choose one decision (none pre-selected):
   - 提案改訂を適用する
   - 追加の修正を依頼する
   - 提案改訂を却下する
   - 保留（追加根拠待ち）
2. Enter rationale (≥ 12 characters).
3. For Apply, tick the explicit confirmation checkbox.
4. Confirm the browser dialog and submit.

Only **Apply** updates Japanese stem/explanation. Accuracy acceptance is a separate form and is **not** granted by Apply.

### How the change is logged

`QuestionImportReviewAudit` rows with action `source_revision_decision` store reviewer ID, notes, timestamps, before/after Japanese text fingerprints, and explicit flags that accuracy / editorial / rights / publication were **not** conferred. Previous wording is retained in audit `before` and in `source_metadata.source_revision_history`.

### How source fingerprints change

Task 45 `reviewContentFingerprint` (stable ID + JA stem + answer + JA explanation + answer-key reference + exact provision) is reused. Material Apply recalculates fingerprints; stale expected-fingerprint submissions are rejected.

### How prior approvals are invalidated

After a material Apply, prior human accuracy acceptance is cleared / marked stale for the new fingerprint. Fresh accuracy review is required. Editorial, rights, answer-key, and publication gates stay closed.

### How translation eligibility is determined afterward

- `:1` / `:8` remain withheld until Apply resolves source clarification for the **current** fingerprint.
- Existing **56** drafts for the other eight candidates are untouched by this workflow.
- Resolution makes the candidate **eligible for later drafting**; it does **not** auto-generate the missing 14 translations or weaken native-review / publication gates.

---

## Distinctions that remain in force

| Statement | Meaning |
| --- | --- |
| Research-supported | Evidence was collected and interpreted |
| Human revision decision | Authorized human accepted/rejected/deferred a **content** proposal |
| Human accuracy-approved | Authorized human accepted the **current** version for legal/answer accuracy |

Completing Task 47 must **not** auto-approve accuracy, clear rights, mark translations reviewed, or publish either candidate.
