# Karimen Original Pilot 001 — Legal Accuracy Audit (Task 44)

**Collection:** `menkyo_original_karimen_pilot_001`  
**Audit date:** 2026-10-11  
**Machine-readable catalog:** `database/data/original_karimen_pilot_001_accuracy_audit.php`  
**Related packet:** `docs/KARIMEN_ORIGINAL_PILOT_001.md`

## Summary

| Metric | Count |
| --- | ---: |
| Total candidates reviewed | 10 |
| `supported_as_written` | 8 |
| `supported_with_clarification` | 2 (`:1`, `:8`) |
| `ambiguous_needs_revision` | 0 |
| `evidence_insufficient` | 0 |
| `potentially_incorrect` | 0 |
| `legal_effective_date_unresolved` | 0 |
| Withheld topics (not in the 10) | 1 (unsigned general-road speed) |

**No candidate was marked human-approved.** Research assessment ≠ human accuracy / editorial / rights / publication approval.

All 10 remain **unpublished** and **ineligible** for public exam pools.

### Official sources actually inspected

| Source | URL | Accessed |
| --- | --- | --- |
| 道路交通法 | https://laws.e-gov.go.jp/law/335AC0000000105/ | 2026-10-11 |
| 道路交通法施行令 | https://laws.e-gov.go.jp/law/335CO0000000270/ | 2026-10-11 |
| 道路標識・区画線・標示命令 | https://laws.e-gov.go.jp/law/335M50004002003/ | 2026-10-11 |
| 警察庁・生活道路法定速度案内 | https://www.npa.go.jp/bureau/traffic/seikatsudouro/seikatsudoro.html | 2026-10-11 |

### 2026 effective-date note

施行令第11条の一般道路法定速度区分（中央線等のない一般道路における自動車30km/h等）は令和8年9月1日以降の枠として確認した。Task 43で除外した「標識なし常に60」命題は再導入していない。既存 `:6` は標識指定最高速度（法第22条第1項）に限定しており、未解決の政令数値命題ではない。

---

## Individual records

### menkyo_original_karimen_pilot_001:1

1. **ID:** `:1`  
2. **Japanese question:** 道路を通行する車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。  
3. **Proposed answer:** 正しい  
4. **Explanation:** Updated in Task 44 to note that Art. 7 also covers 歩行者等; vehicle duty remains accurate.  
5. **Source:** e-Gov 道路交通法  
6. **Provision:** 第7条  
7. **Evidence:** Primary Art. 7 text inspected; supports True for vehicles.  
8. **Exceptions:** Police hand-signal priority context (Art. 6); signal-color meanings out of scope.  
9. **Assessment:** `supported_with_clarification`  
10. **Proposed improvement:** Keep stem; explanation clarified (applied to catalog). Optional: mention 歩行者等 in stem.  
11. **Human review:** Confirm live e-Gov wording; decide beginner stem scope.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:2

1. **ID:** `:2`  
2. **Question:** 交通整理が行われていない交差点…一時停止…停止線の直前（なければ交差点の直前）で一時停止しなければならない。  
3. **Answer:** 正しい  
4. **Explanation:** Distinguishes temporary stop from mere slow travel.  
5–6. **Source / provision:** 第43条  
7. **Evidence:** Matches Art. 43 stop-position rule.  
8. **Exceptions:** Latter clause of Art. 43 / Art. 36(2) interaction not the tested proposition.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** Optional shorten without dropping stop-position conditions.  
11. **Human review:** Stem length for beginners.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:3

1. **ID:** `:3`  
2. **Question:** 横断歩道等により進路前方を横断し又は横断しようとする歩行者等があるときは…一時停止し…妨げない。  
3. **Answer:** 正しい  
4. **Explanation:** Cites Art. 38(1).  
5–6. **Source / provision:** 第38条第1項後段  
7. **Evidence:** Supports True.  
8. **Exceptions:** Approach-speed clause (前段); Art. 38(2) overtaking stopped vehicles.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** None required for accuracy.  
11. **Human review:** Optional separate item for approach-speed duty.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:4

1. **ID:** `:4`  
2. **Question:** 踏切直前で停止・安全確認（信号に従う場合の例外付き）。  
3. **Answer:** 正しい  
4. **Explanation:** Art. 33(1) with signal exception.  
5–6. **Source / provision:** 第33条第1項  
7. **Evidence:** Matches primary text.  
8. **Exceptions:** Signal proviso included; Art. 33(2) covered by `:5`.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** Keep exception if editing.  
11. **Human review:** Length / readability.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:5

1. **ID:** `:5`  
2. **Question:** 警報中でも列車が見えなければ進入してよい。  
3. **Answer:** 誤り  
4. **Explanation:** Art. 33(2) forbids entry while alarm/barrier conditions apply.  
5–6. **Source / provision:** 第33条第2項  
7. **Evidence:** False stem correctly contradicted.  
8. **Exceptions:** Visibility of a train is not a statutory entry condition.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** None.  
11. **Human review:** Naturalness of false stem.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:6

1. **ID:** `:6`  
2. **Question:** 標識等で最高速度が指定されている道路ではその最高速度を超えてはならない。  
3. **Answer:** 正しい  
4. **Explanation:** Ceiling, not target; points to safe-driving duty.  
5–6. **Source / provision:** 第22条第1項（標識指定部分）  
7. **Evidence:** Supported; avoids unsigned 施行令第11条 numerical claim.  
8. **Exceptions:** Unsigned-road ordinance speeds; Art. 70 may require slower travel.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** Do not expand into 30/60 unsigned cases here.  
11. **Human review:** Keep scope limited to posted maxima.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:7

1. **ID:** `:7`  
2. **Question:** Near-statutory Art. 70 safe-driving duty.  
3. **Answer:** 正しい  
4. **Explanation:** Distinguishes from merely staying under the posted maximum.  
5–6. **Source / provision:** 第70条  
7. **Evidence:** Supported.  
8. **Exceptions:** Not etiquette; separate from max-speed compliance.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** Optional beginner paraphrase if originality reviewers prefer less near-verbatim wording.  
11. **Human review:** Originality / educational paraphrase decision.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:8

1. **ID:** `:8`  
2. **Question (current / unchanged):** 交通整理の行われていない交差点においては、第36条第2項が適用される場合を除き、交差道路を左方から進行してくる車両等の進行妨害をしてはならない。  
3. **Answer:** 正しい  
4. **Explanation:** Task 44 clarified that Art. 36(1)(i) also covers streetcars on the intersecting road.  
5–6. **Source / provision:** 第36条第1項第1号・第2項  
7. **Evidence:** Left-priority core is supported; stem omits 路面電車 clause.  
8. **Exceptions:** Art. 36(2) priority/wider road; streetcar-as-subject uses Art. 36(1)(ii).  
9. **Assessment:** `supported_with_clarification`  
10. **Proposed stem (not auto-applied):** 車両は交差道路を左方から進行してくる車両及び交差道路を通行する路面電車の進行妨害をしてはならない（第36条第2項適用時を除く）。  
11. **Human review:** Accept proposed stem vs keep shorter teaching focus.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:9

1. **ID:** `:9`  
2. **Question:** 右折時、直進又は左折しようとする車両等の進行妨害禁止。  
3. **Answer:** 正しい  
4. **Explanation:** Art. 37.  
5–6. **Source / provision:** 第37条  
7. **Evidence:** Matches.  
8. **Exceptions:** Definition of 進行妨害 (Art. 2) is background.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** None required.  
11. **Human review:** Optional cross-reference to Art. 36 left-priority.  
12. **Publication:** unpublished  

### menkyo_original_karimen_pilot_001:10

1. **ID:** `:10`  
2. **Question:** 一時停止指定場所では徐行すれば一時停止不要。  
3. **Answer:** 誤り  
4. **Explanation:** Art. 43 vs Art. 42 are distinct duties.  
5–6. **Source / provision:** 第43条・第42条  
7. **Evidence:** False stem correctly rejected.  
8. **Exceptions:** Art. 43 geographic scope is uncontrolled intersection temporary-stop designations.  
9. **Assessment:** `supported_as_written`  
10. **Improvement:** Optional explicit「交差点で…」 scope wording.  
11. **Human review:** Whether to tighten geographic wording.  
12. **Publication:** unpublished  

---

## Controlled revisions applied vs proposed

| ID | Applied automatically | Proposal only |
| --- | --- | --- |
| `:1` | Explanation clarification | Optional stem mentioning 歩行者等 |
| `:8` | Explanation clarification (streetcars) | Optional stem revision adding 路面電車 |
| Others | Research metadata / admin display fields via restage | None |

Question stems for `:1`–`:10` stable IDs were **not** silently rewritten.

## Originality / similarity

- Catalog drafting origin: AI-assisted independent MenkyoNavi drafting.  
- Conservative exact-match scan against other staging source groups: no automatic rights clearance.  
- Near-statutory `:7` flagged for human originality/editorial judgment.

## Final limitations

| Layer | Status |
| --- | --- |
| Source research completed | Yes (Task 44) |
| Automated consistency checks | Yes |
| Developer / AI editorial drafting | Yes |
| Authorized human accuracy review | **Pending** |
| Authorized human legal review | **Pending** |
| Commercial reuse / provenance review | **Pending** |
| Publication approval | **Pending / blocked** |

```
human_accuracy_review = false
human_editorial_approval = false
answer_key_status = verification_pending
content_rights_status = unclear
is_published = false
public_exam_eligible = false
research_assessment_confers_human_approval = false
```

Do not treat this Markdown file as a reviewer signature.
