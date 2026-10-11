# Road Sign 326 (一方通行) — Legal Classification Review

**Audit date (sources actually checked):** 2026-10-11  
**Task:** MenkyoNavi Task 40  
**Status of research conclusion:** `source_supported`  
**Human editorial approval:** **not granted** (developer/source research only)

This document distinguishes:

| Layer | Meaning in this audit |
| --- | --- |
| Authoritative source evidence | Text and visual placement taken from e-Gov / MLIT public pages checked on the audit date |
| Developer research conclusion | Application of that evidence to MenkyoNavi’s catalog |
| Human editorial approval | Explicit human sign-off — **not claimed here** |

---

## 1. Question investigated

Does the Japanese road sign **一方通行** (official codes **326-A** / **326-B**) belong to:

- **規制標識** (regulatory signs), or
- **指示標識** (instruction signs)?

Secondary checks: official meaning, distinction from **326の2-A/B**, and consistency of MenkyoNavi educational copy.

---

## 2. Existing application classification (before this audit)

| Field | Value in `config/content/road_signs.php` |
| --- | --- |
| Sign id | `one-way` |
| Official name (JA) | 一方通行 |
| Code (catalog) | `326` |
| Category key | `regulatory` |
| Category official JA | 規制標識 |
| Review state | `verified` |
| Locales | All eight locale files teach 一方通行 as a regulatory / 規制標識 entry |

**No category change was required.** Prior uncertainty was a review flag, not an incorrect live classification.

---

## 3. Official name and codes

| Item | Official value |
| --- | --- |
| Name | 一方通行 |
| Codes | **326-A**, **326-B** (別表表記: （３２６―Ａ・Ｂ） / （３２６―Ａ）, （３２６―Ｂ）) |
| Related but distinct | 特定小型原動機付自転車・自転車一方通行 — **326の2-A**, **326の2-B** |
| Not this sign | 指定方向外進行禁止 (311-A〜F) |

---

## 4. e-Gov evidence

**Law:** 道路標識、区画線及び道路標示に関する命令  
**URL:** https://laws.e-gov.go.jp/law/335M50004002003/  
**Checked:** 2026-10-11

### Provisions / sections consulted

1. **第一条第二項** — 本標識は、案内標識、警戒標識、規制標識及び指示標識とする。
2. **第二条 / 別表第一** — 道路標識の種類、設置場所等:
   - Under the **規制標識** schedule entry for **（３２６―Ａ・Ｂ）一方通行**:
     - Meaning (要約ではなく命令文):  
       「道路法第四十六条第一項の規定に基づき、又は交通法第八条第一項の道路標識により、**標示板の矢印が示す方向の反対方向にする車両の通行を禁止すること**。」
     - Placement: 一定の方向にする車両の通行を禁止する道路の区間の入口及び道路の区間内の必要な地点における路端
   - Immediately after: **特定小型原動機付自転車・自転車一方通行（３２６の２―Ａ・Ｂ）** — opposite-direction prohibition limited to 特定小型原動機付自転車 and 自転車 (not the general 一方通行 rule).
3. **別表第二（様式）** — under heading **規制標識**, listed:
   - 一方通行（３２６―Ａ）
   - 一方通行（３２６―Ｂ）
   - 特定小型原動機付自転車・自転車一方通行（３２６の２―Ａ）
   - 特定小型原動機付自転車・自転車一方通行（３２６の２―Ｂ）
4. **様式色規定** — 「一方通行」及び「特定小型原動機付自転車・自転車一方通行」を表示するものについては、記号及び縁線を白色、縁及び地を青色とする。
5. **指示標識** schedule (別表) — begins with 並進可（４０１）, 軌道敷内通行可（４０２）, … 優先道路（４０５）, etc. **一方通行 does not appear under 指示標識.**

---

## 5. MLIT evidence

### 5a. Official road-sign list (visual category layout)

**Title:** 道路標識一覧  
**URL:** https://www.mlit.go.jp/road/sign/sign/douro/ichiran.pdf  
**Organization:** 国土交通省 道路局  
**Checked:** 2026-10-11

Under the composite sheet headed **警戒標識 / 規制標識 / 指示標識 / 補助標識**:

- **(326-A) 一方通行** and **(326-B) 一方通行** appear in the **規制標識** grouping.
- **(326の2-A)** / **(326の2-B)** 特定小型原動機付自転車・自転車一方通行 appear as separate regulatory entries.
- Instruction-group examples nearby include **(405) 優先道路**, **(407-A/B) 横断歩道**, etc. — not 326.

### 5b. Category explanation page

**Title:** 道路標識の種類について教えてください（道の相談室）  
**URL:** https://www.mlit.go.jp/road/soudan/soudan_04a_01.html  
**Checked:** 2026-10-11

MLIT explains four primary families: 案内 / 警戒 / **規制** / **指示**, with 規制 described as covering prohibitions and similar controls (例: 駐車禁止や一時停止など). This page supports the family taxonomy; the **326 placement itself** is taken from the list PDF and the e-Gov 別表, not from this FAQ alone.

---

## 6. Interpretation and limitations

### Developer research conclusion

1. **Classification:** 一方通行（326-A/B） is a **規制標識**.
2. **Meaning for learners:** Vehicles must not travel opposite the arrow direction shown on the plate; travel is constrained to the arrow-indicated direction on the regulated section.
3. **Variants:** 326-A and 326-B share the same legal meaning; they are plate-shape / mounting variants (commonly described as 横型 / 縦型), not different rules.
4. **Do not conflate with:**
   - 326の2-A/B (vehicle classes limited to 特定小型原動機付自転車・自転車)
   - 指定方向外進行禁止 (311)
   - 車両進入禁止 (303), often paired at exits but a different sign

### Limitations (explicit)

- This audit does **not** interpret every local exception created by 補助標識 (時間帯・車両の種類など).
- Exit-side pairing with 303 is educational practice context, not a universal statutory pairing mandate restated here as absolute.
- Educational SVGs are original schematics, not official reproductions.
- Draft locale translations remain **not** native-speaker approved.

---

## 7. Whether application data needed correction

| Item | Result |
| --- | --- |
| Category `regulatory` / 規制標識 | **Already correct — kept unchanged** |
| Official name 一方通行 | Already correct |
| Educational meaning (vehicles opposite arrow prohibited) | Already aligned with 別表 meaning |
| Catalog code display | Remains `326` as the family code; audit metadata now records official variants `326-A` / `326-B` |
| Illustrated variant | Educational SVG is a **vertical (tall) blue plate with white upward arrow** → recorded as illustrated variant **326-B** |
| Locale draft/noindex statuses | Unchanged |
| Recognition-practice answer map | Unchanged |

---

## 8. Affected localization / UI fields

Touched only as needed for evidence transparency and variant clarity:

- `config/content/road_signs.php` — sources, one-way metadata (`official_variants`, `illustrated_variant`, `classification_research`)
- `app/Support/Content/RoadSignsGuide.php` — expose audit fields on enriched signs
- `docs/ROAD_SIGN_326_LEGAL_REVIEW.md` — this file
- Focused PHPUnit coverage

Eight-language educational meaning for `one-way` was reviewed read-only: all locales already classify the sign as regulatory and teach opposite-direction **vehicle** prohibition. No full rewrite of the 17-sign library.

---

## 9. Remaining review requirements

- Human legal/editorial approval of learning copy (still open).
- Native-speaker review of draft locales (en/zh/vi/fil/ne/bn/ko remain `draft_translation` / noindex).
- Optional future lesson: dedicated comparison card for 326の2 vs 326 (out of scope here).
- Railroad-crossing code discrepancy (`201` vs **207-A/B**) was corrected in Task 41; see `docs/ROAD_SIGN_207_LEGAL_REVIEW.md`.

---

## 10. Approval boundary

```
classification_research.status = source_supported
human_editorial_approval = false
human_legal_approval = false
native_review = false (non-JA locales)
```

Do not treat this Markdown file as a reviewer signature.
