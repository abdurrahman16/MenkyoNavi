# Road Sign 207 (踏切あり) — Official Code Correction Review

**Audit date (sources actually checked):** 2026-10-11  
**Task:** MenkyoNavi Task 41  
**Status of research conclusion:** `source_supported`  
**Human editorial approval:** **not granted**  
**Human legal approval:** **not granted**

| Layer | Meaning in this audit |
| --- | --- |
| Authoritative source evidence | e-Gov / MLIT text and list placement checked on the audit date |
| Visual comparison | Educational SVG compared to known official 207-A / 207-B pictograms |
| Developer research conclusion | Catalog code correction and variant-status recording |
| Human editorial / legal approval | Explicit human sign-off — **not claimed here** |

---

## 1. Original problem

The Road Signs catalog recorded **踏切あり** (`railroad-crossing`) with official code **`201`**.

That number belongs to the **intersection-shape warning family**, not the railroad-crossing warning:

| Sign | Official codes |
| --- | --- |
| ┼形道路交差点あり | **201-A** |
| 踏切あり | **207-A**, **207-B** |

Task 40 already flagged this as an unresolved discrepancy.

---

## 2. Previous catalog code

| Field | Before Task 41 |
| --- | --- |
| Internal id | `railroad-crossing` (unchanged) |
| Official name JA | 踏切あり |
| Catalog `code` | **`201`** (incorrect) |
| Category | `warning` / 警戒標識 |
| Crossroads `code` | `201-A` (already correct) |

---

## 3. Confirmed official code

| Item | Confirmed value |
| --- | --- |
| Official name | 踏切あり |
| Official variants | **207-A**, **207-B** |
| Catalog family code after correction | **`207`** |
| Category | **警戒標識** (`warning`) |
| Placement (別表第一) | 鉄道又は軌道（併用軌道を除く。）との交差地点の手前五十メートルから百二十メートルまでの地点における左側の路端 |

---

## 4. e-Gov evidence

**Law:** 道路標識、区画線及び道路標示に関する命令  
**URL:** https://laws.e-gov.go.jp/law/335M50004002003/  
**Checked:** 2026-10-11

### Provisions / sections consulted

1. **別表第一 警戒標識**
   - **┼形道路交差点あり（２０１―Ａ）** — intersection ahead (distinct from 踏切あり).
   - **踏切あり（２０７―Ａ・Ｂ）** — railroad / tramway crossing ahead (excluding 併用軌道).
2. **別表第二（様式）警戒標識** lists:
   - ┼形道路交差点あり（２０１―Ａ）
   - … intermediate warning signs …
   - **踏切あり（２０７―Ａ）**
   - **踏切あり（２０７―Ｂ）**
3. Related auxiliary notice **（５０９の２）踏切注意** references the 警戒標識 「踏切あり」 — it is not a substitute primary code for the diamond warning.

---

## 5. MLIT evidence

**Title:** 道路標識一覧  
**URL:** https://www.mlit.go.jp/road/sign/sign/douro/ichiran.pdf  
**Organization:** 国土交通省 道路局  
**Checked:** 2026-10-11

On the composite warning / regulatory / instruction / supplementary sheet:

- **(201-A) ＋形道路交差点あり** appears among intersection-shape warnings.
- **(207-A) 踏切あり** and **(207-B) 踏切あり** appear as the railroad-crossing warnings.
- The two families are visually and numerically distinct.

---

## 6. Difference between 201-A and 207-A/B

| Code | Official name | Meaning family |
| --- | --- | --- |
| **201-A** | ┼形道路交差点あり (MLIT sheet also writes ＋形) | Roads cross ahead |
| **207-A** | 踏切あり | Railroad crossing ahead — official pictogram historically associated with a **steam locomotive** silhouette |
| **207-B** | 踏切あり | Same legal name/meaning — official pictogram associated with an **electric train / EMU** silhouette |

A and B for 207 share the same warning function; they differ in the **vehicle pictogram**, not in the underlying “crossing ahead” rule.

---

## 7. Visual variant — Task 41 finding (superseded by Task 42)

Task 41 recorded the then-current SVG as an **educational simplification** (X-mark / crossing ticks), not labeled as 207-A or 207-B.

## 7b. Task 42 illustration upgrade (207-B educational pictogram)

**Date checked:** 2026-10-11  
**Official figures inspected:**

| Source | What was inspected |
| --- | --- |
| e-Gov 別表第二 listing | 踏切あり（２０７―Ａ） / （２０７―Ｂ） under 警戒標識 |
| MLIT `ichiran.pdf` | Visual placement of (207-A) and (207-B) |
| Official-style 別表第2-based vector (Wikimedia “Japan road sign 207-B.svg”, sourced from the Order’s 別表第2) | Electric-train side profile: pantograph, cab + passenger windows, bogies, yellow diamond |

**Previous schematic:** black X / crossing ticks inside yellow diamond.  
**New intended variant:** original educational **207-B** electric-train pictogram.

| Observation after Task 42 | Finding |
| --- | --- |
| Plate shape / color | Yellow diamond `#F7D117`, black stroke — matches sibling warning SVGs |
| Interior symbol | Black **electric train** facing left with pantograph, cab windshield, passenger windows, bogies, track/skirt |
| Steam locomotive (207-A) | **Not depicted** |
| Electric train (207-B) | **Depicted** (educational original; not a byte-for-byte official reproduction) |
| Source matching | Confirmed against official-style 207-B figure features (pantograph + EMU side windows + bogies) |
| Scene reuse | `road-signs-scene-warning.svg` updated to the same pictogram |

Catalog fields after Task 42:

```
official_variants = ['207-A', '207-B']
illustrated_variant = '207-B'
illustration_variant_status = educational_207b_source_matched
```

Visual QA performed: side-by-side HTML comparison (400px / 132px card) against the 別表第2-based reference; library filter page load for warning + `207`.

---

## 8. Files corrected

### Task 41
- Catalog code `201` → `207`, variants, research metadata; crossroads official name **┼形道路交差点あり**
- Evidence + regression tests for the code correction

### Task 42
- `public/images/learn/signs/railroad-crossing.svg` — original 207-B educational pictogram
- `public/images/learn/road-signs-scene-warning.svg` — same pictogram in context scene
- `config/content/road_signs.php` — `illustrated_variant` / status update
- `lang/*/road_signs.php` — remove “simplified X” wording; state 207-B is shown
- `docs/ROAD_SIGN_207_LEGAL_REVIEW.md` — this section
- `tests/Feature/RoadSign207BIllustrationTest.php` — illustration regression coverage
- `tests/Feature/RoadSign207LegalReviewTest.php` — variant assertions updated

---

## 9. Translation impact

- Internal id `railroad-crossing` unchanged; practice answer mapping unchanged.
- Draft locale statuses unchanged (`draft_translation` / noindex).
- Locales now state the learning image shows **207-B (electric train)** and that **207-A** remains the other official variant.
- Not marked native-reviewed.

---

## 10. Remaining editorial questions

- Human editorial / legal approval of learning copy and illustration fidelity.
- Native-speaker review of draft locales.
- Optional future: dedicated 207-A comparison illustration (out of scope).
- Crossroads educational titles still say “十字路” while catalog official name is **┼形道路交差点あり**.

---

## 11. Date actually checked

**2026-10-11** — e-Gov law page, MLIT `ichiran.pdf`, and official-style 207-B figure inspected for Tasks 41–42.

---

## Approval boundary

```
classification_research.status = source_supported
previous_catalog_code = 201
corrected_catalog_code = 207
illustrated_variant = 207-B
illustration_variant_status = educational_207b_source_matched
human_editorial_approval = false
human_legal_approval = false
```

Do not treat this Markdown file as a reviewer signature.
