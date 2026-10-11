# Karimen Original Pilot 001 — Editorial Review Packet

**Collection key:** `menkyo_original_karimen_pilot_001`  
**Date drafted:** 2026-10-11  
**Intended use:** Private editorial candidates for original Japanese provisional-license learning  
**Publication eligibility:** No  
**Official exam claim:** None — these are not official examination questions  
**Accuracy audit (Task 44):** See `docs/KARIMEN_ORIGINAL_PILOT_001_ACCURACY_AUDIT.md` — research assessments only; **not** human approval.

## Collection provenance

| Field | Value |
| --- | --- |
| Origin | Independently drafted for MenkyoNavi with AI assistance from primary legal research |
| Not derived from | `karimen_exam_trainer_en_610`, `karimen_exam_trainer_en_414`, demo questions |
| Language | Japanese (authoritative) |
| Format | True / False (`正しい` / `誤り`) |
| Staging command | `php artisan menkyo:stage-original-karimen-pilot --dry-run` then `--commit` |
| Human accuracy review | Pending (`false`) |
| Human editorial approval | Pending (`false`) |
| Rights / provenance confirmation | Pending (`false`) |
| Translation readiness | Not ready (JA draft only; EN gloss non-canonical) |

Source-backed research is **not** human approval.

## Primary sources consulted

| Source | URL |
| --- | --- |
| 道路交通法 (e-Gov) | https://laws.e-gov.go.jp/law/335AC0000000105/ |
| 道路交通法施行令 (e-Gov) | https://laws.e-gov.go.jp/law/335CO0000000270/ |
| NPA life-road speed notice (context for withheld speed item) | https://www.npa.go.jp/bureau/traffic/seikatsudouro/seikatsudoro.html |

## Withheld topics

| Subject | Reason |
| --- | --- |
| Unsigned general-road statutory speed (60 vs 30 km/h) | 施行令第11条 was revised for 令和8年9月1日 onward; a simplistic “always 60 km/h without a sign” stem would be unreliable. Excluded rather than inventing a citation. |

## Candidate index

| ID | Subject | Answer | Evidence |
| --- | --- | --- | --- |
| `:1` | Traffic signals | 正しい | Art. 7 |
| `:2` | Temporary stop | 正しい | Art. 43 |
| `:3` | Pedestrian crossings | 正しい | Art. 38(1) |
| `:4` | Railroad crossing stop | 正しい | Art. 33(1) |
| `:5` | Railroad alarm entry | 誤り | Art. 33(2) |
| `:6` | Posted maximum speed | 正しい | Art. 22(1) |
| `:7` | Safe driving duty | 正しい | Art. 70 |
| `:8` | Intersection left priority | 正しい | Art. 36(1)–(2) |
| `:9` | Right-turn yield | 正しい | Art. 37 |
| `:10` | Stop vs slow myth | 誤り | Art. 43 / 42 |

---

## Candidate detail

### menkyo_original_karimen_pilot_001:1

- **Subject:** Traffic signals / 信号機
- **Japanese question:** 道路を通行する車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第7条に基づく。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第7条（信号機の信号等に従う義務）
- **Evidence status:** `evidence_collected` (automated research)
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Confirm live e-Gov wording before sign-off
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:2

- **Subject:** Temporary stop / 一時停止
- **Japanese question:** 交通整理が行われていない交差点又はその手前の直近において、道路標識等により一時停止すべきことが指定されているときは、道路標識等による停止線の直前（停止線がない場合は交差点の直前）で一時停止しなければならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第43条。徐行では足りない。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第43条（指定場所における一時停止）
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Stem length tracks statute; may shorten carefully
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:3

- **Subject:** Pedestrian crossings / 横断歩道
- **Japanese question:** 横断歩道等によりその進路の前方を横断し、又は横断しようとする歩行者等があるときは、車両等は当該横断歩道等の直前で一時停止し、かつ、その通行を妨げないようにしなければならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第38条第1項。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第38条第1項
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Does not separately test approach-speed clause
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:4

- **Subject:** Railroad crossing / 踏切
- **Japanese question:** 車両等は、踏切を通過しようとするときは、踏切の直前（停止線があるときはその直前）で停止し、かつ、安全であることを確認した後でなければ進行してはならない。ただし、信号機の表示する信号に従うときは、踏切の直前で停止しないで進行することができる。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第33条第1項（信号例外付き）。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第33条第1項
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Keep the signal exception if editing
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:5

- **Subject:** Railroad alarm / 踏切警報
- **Japanese question:** 踏切の警報機が警報している間でも、列車が見えなければ踏切に進入してよい。
- **Proposed answer:** 誤り (false)
- **Explanation:** 道路交通法第33条第2項は警報中の進入を禁じる。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第33条第2項
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** False stem; check natural Japanese
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:6

- **Subject:** Maximum speed / 最高速度
- **Japanese question:** 車両は、道路標識等によりその最高速度が指定されている道路においては、その最高速度を超える速度で進行してはならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第22条第1項。上限であり目標速度ではない。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第22条第1項
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Avoid unsigned 60/30 numerical expansions without checking 施行令第11条
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:7

- **Subject:** Safe driving duty / 安全運転義務
- **Japanese question:** 車両等の運転者は、当該車両等のハンドル、ブレーキその他の装置を確実に操作し、かつ、道路、交通及び当該車両等の状況に応じ、他人に危害を及ぼさないような速度と方法で運転しなければならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第70条。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第70条（安全運転の義務）
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Near-statutory paraphrase; originality review may request rewording
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:8

- **Subject:** Intersection priority / 交差点優先
- **Japanese question:** 交通整理の行われていない交差点においては、道路交通法第36条第2項が適用される場合を除き、交差道路を左方から進行してくる車両等の進行妨害をしてはならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 左方優先。優先道路・明らかな広幅員は第36条第2項。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第36条第1項・第2項
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Stem intentionally mentions Art. 36(2) exception
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:9

- **Subject:** Right-turn yield / 右折
- **Japanese question:** 車両等は、交差点で右折する場合において、当該交差点において直進し、又は左折しようとする車両等があるときは、当該車両等の進行妨害をしてはならない。
- **Proposed answer:** 正しい (true)
- **Explanation:** 道路交通法第37条。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第37条
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** None
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

### menkyo_original_karimen_pilot_001:10

- **Subject:** Temporary stop vs slow / 一時停止と徐行
- **Japanese question:** 道路標識等により一時停止すべきことが指定されている場所では、徐行すれば一時停止しなくてもよい。
- **Proposed answer:** 誤り (false)
- **Explanation:** 第43条は一時停止を要求。第42条の徐行は別義務。
- **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/
- **Provision:** 道路交通法 第43条、第42条
- **Evidence status:** `evidence_collected`
- **Drafting origin:** AI-assisted independent drafting from primary statute
- **Ambiguities / reviewer notes:** Common learner confusion
- **Human accuracy review:** false
- **Human editorial approval:** false
- **Translation readiness:** false
- **Publication eligibility:** false

---

## Admin review path

1. Run `php artisan menkyo:stage-original-karimen-pilot --commit` in a non-production environment.
2. Open Admin → Question bank browse.
3. Filter `source_key` = `menkyo_original_karimen_pilot_001`.
4. Open each entry: Japanese draft, proposed answer, explanation, source URL/section, review flags, publication blockers.

## Approval boundary

```
human_accuracy_review = false
human_editorial_approval = false
translation_reviewed = false
published = false
public_exam_eligible = false
```

Do not treat this Markdown file as a reviewer signature.
