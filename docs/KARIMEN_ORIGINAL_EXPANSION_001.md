# Karimen Original Expansion 001 — Editorial Packet (Task 48)

**Collection:** `menkyo_original_karimen_expansion_001`  
**Prepared:** 2026-10-11  
**Sources accessed:** 2026-10-11  
**Staging:** `php artisan menkyo:stage-original-karimen-expansion {--dry-run|--commit} {--batch=A|B|C|D}`  
**Service:** `App\Services\Editorial\OriginalKarimenExpansionStagingService`

Research-supported drafts only. **Human-approved count: 0.** **Published count: 0.**

Pilot candidates `:1`–`:10` and Task 47 revision proposals are **unchanged**. Task 46 translation drafts (**56**) are **unchanged**. No seven-locale translations were generated for these 40 candidates.

## Summary

| Metric | Count |
| --- | ---: |
| Target new candidates | 40 |
| Actual drafted | 40 |
| Batch A (11–20) | 10 |
| Batch B (21–30) | 10 |
| Batch C (31–40) | 10 |
| Batch D (41–50) | 10 |
| `supported_as_written` | 37 |
| `supported_with_clarification` | 3 |
| Evidence-unavailable / withheld drafts | 0 |
| Human-approved | 0 |
| Published | 0 |

### Topic distribution (drafting targets were approximate)

| Topic bucket | Count |
| --- | ---: |
| signals | 3 |
| signs | 6 |
| pedestrian | 6 |
| intersection | 6 |
| turning | 5 |
| stopping | 4 |
| passing | 3 |
| speed | 4 |
| other | 3 |

### Official sources examined

- 道路交通法（e-Gov）: https://laws.e-gov.go.jp/law/335AC0000000105/ (accessed 2026-10-11)
- 道路交通法施行令（e-Gov, reference only; no unsigned-speed stems）: https://laws.e-gov.go.jp/law/335CO0000000270/ (accessed 2026-10-11)
- 道路標識、区画線及び道路標示に関する命令（e-Gov, reference）: https://laws.e-gov.go.jp/law/335M50004002003/ (accessed 2026-10-11)

Road Traffic Act text inspected on e-Gov (access 2026-10-11). Unsigned-road 施行令第11条 numerical 30/60 claims remain excluded.

---

## Batch A

### menkyo_original_karimen_expansion_001:11

1. **ID:** `menkyo_original_karimen_expansion_001:11`  
2. **Topic:** 警察官等の手信号 (`police_hand_signals` / signals)  
3. **Japanese question:** 警察官等が交通整理のため信号機の表示する信号と異なる意味の手信号等をしたときは、道路を通行する歩行者等又は車両等は、その手信号等に従わなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第6条第1項後段は、特に必要があるときに信号機と異なる意味の手信号等をできるとし、第7条は前条第一項後段の場合において当該手信号等に従う義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第6条第1項後段・第7条  
8. **Conditions/exceptions:** 手信号等の具体的意味は政令で定める（第6条第5項）。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm live e-Gov Art. 6–7 wording.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:12

1. **ID:** `menkyo_original_karimen_expansion_001:12`  
2. **Topic:** 信号無視の誤解 (`signal_obedience_myth` / signals)  
3. **Japanese question:** 交差する交通がないことが明らかなときは、赤色の灯火の信号であっても車両等は進行してよい。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第7条は、信号機の表示する信号に従う義務を定めており、交差交通の有無を例外条件としていません。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第7条  
8. **Conditions/exceptions:** 警察官等の手信号等が優先される場合（第6条第1項後段）。個別信号色の意味は政令。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm beginner wording of the false stem.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:13

1. **ID:** `menkyo_original_karimen_expansion_001:13`  
2. **Topic:** 通行禁止の道路標識 (`road_sign_prohibition` / signs)  
3. **Japanese question:** 歩行者等又は車両等は、道路標識等によりその通行を禁止されている道路又はその部分を通行してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第8条第1項は、道路標識等による通行禁止に従う義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第8条第1項  
8. **Conditions/exceptions:** 警察署長の許可がある場合の例外（第8条第2項）。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Keep Art. 8(2) permit exception in explanation if shortening.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:14

1. **ID:** `menkyo_original_karimen_expansion_001:14`  
2. **Topic:** 通行禁止の近道誤解 (`road_sign_shortcut_myth` / signs)  
3. **Japanese question:** 道路標識等により通行が禁止されている道路の部分でも、距離が短い近道であれば車両は通行してよい。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第8条第1項は通行禁止に従う義務を定め、近道であることは例外ではありません（許可がある場合等は別）。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第8条第1項  
8. **Conditions/exceptions:** 第8条第2項の許可。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm natural false-stem Japanese.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:15

1. **ID:** `menkyo_original_karimen_expansion_001:15`  
2. **Topic:** 横断歩道への接近速度 (`crosswalk_approach_speed` / pedestrian)  
3. **Japanese question:** 車両等は、横断歩道等に接近する場合、当該横断歩道等により進路前方を横断しようとする歩行者等がないことが明らかな場合を除き、横断歩道等の直前で停止できるような速度で進行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第38条第1項前段は、歩行者等がないことが明らかな場合を除き、停止できる速度で接近する義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第38条第1項前段  
8. **Conditions/exceptions:** 歩行者等がいるときの一時停止義務は同項後段（別命題）。信号等で横断禁止の横断歩道等は第2項で除外される場合あり。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional shorten without dropping the “明らかな場合を除き” carve-out.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:16

1. **ID:** `menkyo_original_karimen_expansion_001:16`  
2. **Topic:** 横断歩道のない交差点の歩行者 (`no_crosswalk_pedestrian_priority` / pedestrian)  
3. **Japanese question:** 車両等は、交差点又はその直近で横断歩道の設けられていない場所において歩行者が道路を横断しているときは、その歩行者の通行を妨げてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第38条の2は、横断歩道のない交差点等で横断中の歩行者の通行妨害を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第38条の2  
8. **Conditions/exceptions:** 横断歩道がある場合の優先は第38条。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm beginner readability.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:17

1. **ID:** `menkyo_original_karimen_expansion_001:17`  
2. **Topic:** 見通し・優先道路と徐行 (`intersection_slow_wider_priority` / intersection)  
3. **Japanese question:** 車両等は、交通整理の行われていない交差点に入ろうとする場合において、交差道路が優先道路であるとき、又はその通行している道路の幅員よりも交差道路の幅員が明らかに広いものであるときは、徐行しなければならない（自らが優先道路を通行している場合を除く）。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第36条第3項は、優先道路を通行している車両等を除き、交差道路が優先道路又は明らかに広いときに交差点進入時の徐行を求めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第36条第3項  
8. **Conditions/exceptions:** 第36条第2項の進行妨害禁止は別命題。環状交差点は第37条の2。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Accept length or request shorter teaching stem?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:18

1. **ID:** `menkyo_original_karimen_expansion_001:18`  
2. **Topic:** 右方優先の誤解 (`right_priority_myth` / intersection)  
3. **Japanese question:** 交通整理の行われていない交差点では、常に右方から進行してくる車両等を優先しなければならない。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第36条第1項は、次項適用時を除き、車両について左方からの車両及び交差道路の路面電車などの進行妨害禁止を定めており、一般的な「右方優先」ではありません。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第36条第1項  
8. **Conditions/exceptions:** 第36条第2項の優先道路・広幅員。環状交差点は別則。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm false stem is unambiguous for beginners.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:19

1. **ID:** `menkyo_original_karimen_expansion_001:19`  
2. **Topic:** 見通しのきかない交差点の徐行 (`blind_corner_slow` / intersection)  
3. **Japanese question:** 車両等は、左右の見とおしがきかない交差点に入ろうとするときは、当該交差点において交通整理が行われている場合及び優先道路を通行している場合を除き、徐行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第42条第1号は、見通しのきかない交差点への進入等について、交通整理・優先道路の場合を除き徐行を求めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第42条第1号  
8. **Conditions/exceptions:** 曲がり角・坂頂・急な下り坂は同条第2号。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** None required for accuracy.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:20

1. **ID:** `menkyo_original_karimen_expansion_001:20`  
2. **Topic:** 左折の方法 (`left_turn_method` / turning)  
3. **Japanese question:** 車両は、左折するときは、あらかじめその前からできる限り道路の左側端に寄り、かつ、できる限り道路の左側端に沿つて（道路標識等により通行すべき部分が指定されているときはその指定された部分を通行して）徐行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第34条第1項は左折時の寄る位置と徐行を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第34条第1項  
8. **Conditions/exceptions:** 環状交差点は第35条の2。指定通行区分は第35条。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Accept near-statutory paraphrase for teaching?  
11. **Publication status:** unpublished  

## Batch B

### menkyo_original_karimen_expansion_001:21

1. **ID:** `menkyo_original_karimen_expansion_001:21`  
2. **Topic:** 信号義務と歩行者等 (`signal_covers_pedestrians` / signals)  
3. **Japanese question:** 道路を通行する歩行者等も、信号機の表示する信号又は警察官等の手信号等に従わなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第7条の義務主体は「歩行者等又は車両等」です。歩行者等にも信号等への従義務があります。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第7条  
8. **Conditions/exceptions:** 手信号優先の括弧書（第6条第1項後段）。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm no confusion with pilot :1.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:22

1. **ID:** `menkyo_original_karimen_expansion_001:22`  
2. **Topic:** 横断・転回・後退の禁止標識 (`no_uturn_sign` / signs)  
3. **Japanese question:** 車両は、道路標識等により横断、転回又は後退が禁止されている道路の部分においては、当該禁止された行為をしてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第25条の2第2項は、標識等による横断・転回・後退の禁止に従う義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第25条の2第2項  
8. **Conditions/exceptions:** 第1項の一般的妨害禁止は別命題。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm figure-free wording is acceptable.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:23

1. **ID:** `menkyo_original_karimen_expansion_001:23`  
2. **Topic:** 横断歩道手前の停止車両の側方通過 (`pass_stopped_at_crosswalk` / pedestrian)  
3. **Japanese question:** 車両等は、横断歩道等又はその手前の直前で停止している車両等の側方を通過してその前方に出ようとするときは、その前方に出る前に一時停止しなければならない（当該横断歩道等による歩行者等の横断が信号等により禁止されている場合を除く）。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第38条第2項は、横断歩道等（又はその手前）で停止中の車両等の側方を通過して前方に出る前の一時停止を求めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第38条第2項  
8. **Conditions/exceptions:** 信号等で横断禁止の横断歩道等は除外。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Length vs clarity trade-off.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:24

1. **ID:** `menkyo_original_karimen_expansion_001:24`  
2. **Topic:** 自転車は歩行者等でないとの誤解 (`bicycle_not_pedestrian_myth` / pedestrian)  
3. **Japanese question:** 横断歩道等で進路前方を横断しようとする自転車があっても、道路交通法第38条第1項後段の一時停止義務の対象となる歩行者等には含まれない。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。第38条第1項は「歩行者又は自転車（以下この条において「歩行者等」という。）」と定義しており、自転車も含まれます。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第38条第1項  
8. **Conditions/exceptions:** 特定小型原動機付自転車等の扱いの細部は本問対象外。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm false stem polarity is clear.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:25

1. **ID:** `menkyo_original_karimen_expansion_001:25`  
2. **Topic:** 右折時の進行妨害禁止 (`right_turn_yield` / intersection)  
3. **Japanese question:** 車両等は、交差点で右折する場合において、当該交差点において直進し、又は左折しようとする車両等があるときは、当該車両等の進行妨害をしてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第37条は、右折する車両等が直進又は左折しようとする車両等の進行を妨げてはならないと定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第37条  
8. **Conditions/exceptions:** 左方優先（第36条）とは別義務。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm originality vs pilot :9 wording.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:26

1. **ID:** `menkyo_original_karimen_expansion_001:26`  
2. **Topic:** 優先道路への進行妨害禁止 (`priority_road_yield` / intersection)  
3. **Japanese question:** 車両等は、交通整理の行われていない交差点において、交差道路が優先道路であるときは、当該交差道路を通行する車両等の進行妨害をしてはならない（自らが優先道路を通行している場合を除く趣旨の第36条第2項本文の枠内）。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第36条第2項は、自らの道路が優先道路である場合を除き、交差道路が優先道路等のときにその車両等の進行妨害を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第36条第2項  
8. **Conditions/exceptions:** 明らかに広い交差道路の場合も同項。優先道路の定義は同項括弧書。  
9. **Research assessment:** `supported_with_clarification`  
10. **Human-review questions:** Simplify stem while keeping priority-road carve-out?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:27

1. **ID:** `menkyo_original_karimen_expansion_001:27`  
2. **Topic:** 交差点での停車禁止 (`no_stop_intersection` / stopping)  
3. **Japanese question:** 車両は、交差点においては、法令の規定若しくは警察官の命令により、又は危険を防止するため一時停止する場合のほか、停車し、又は駐車してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第44条第1項第1号は、交差点等での停車・駐車を原則禁止しています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第44条第1項第1号  
8. **Conditions/exceptions:** 第44条第2項の適用除外。標識による禁止場所も含む同項柱書。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional mention of other Art. 44 locations in explanation only.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:28

1. **ID:** `menkyo_original_karimen_expansion_001:28`  
2. **Topic:** 横断歩道上の駐車誤解 (`park_on_crosswalk_myth` / stopping)  
3. **Japanese question:** 短時間であれば、横断歩道上に車両を駐車してもよい。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第44条第1項第1号は横断歩道での停車・駐車を原則禁止しており、短時間であることは例外ではありません。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第44条第1項第1号  
8. **Conditions/exceptions:** 法令・警察官命令・危険防止の一時停止は別。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm “駐車” vs “停車” wording for beginners.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:29

1. **ID:** `menkyo_original_karimen_expansion_001:29`  
2. **Topic:** 追越しは右側 (`overtake_on_right` / passing)  
3. **Japanese question:** 車両は、他の車両を追い越そうとするときは、その追い越されようとする車両の右側を通行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第28条第1項は、追越しは原則として前車の右側を通行すると定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第28条第1項  
8. **Conditions/exceptions:** 左側追越しとなる場合（第28条第2項）。路面電車は第3項。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Keep exceptions in explanation.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:30

1. **ID:** `menkyo_original_karimen_expansion_001:30`  
2. **Topic:** 横断歩道手前の追越し禁止 (`no_overtake_near_crosswalk` / passing)  
3. **Japanese question:** 車両は、横断歩道及びその手前の側端から前に三十メートル以内の部分においては、他の車両（特定小型原動機付自転車等を除く。）を追い越すため、進路を変更し、又は前車の側方を通過してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第30条第3号は、横断歩道等及びその手前30メートル以内での追越しのための進路変更・側方通過を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第30条第3号  
8. **Conditions/exceptions:** 優先道路上の交差点除外など同号括弧。標識による追越し禁止場所も含む第30条柱書。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm 30m figure against live text.  
11. **Publication status:** unpublished  

## Batch C

### menkyo_original_karimen_expansion_001:31

1. **ID:** `menkyo_original_karimen_expansion_001:31`  
2. **Topic:** 安全地帯への進入禁止 (`safety_zone_entry_ban` / signs)  
3. **Japanese question:** 車両は、安全地帯又は道路標識等により車両の通行の用に供しない部分であることが表示されているその他の道路の部分に入つてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第17条第6項は、安全地帯等への進入を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第17条第6項  
8. **Conditions/exceptions:** 緊急自動車等の別則は本問対象外。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** None.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:32

1. **ID:** `menkyo_original_karimen_expansion_001:32`  
2. **Topic:** 左側通行の原則 (`keep_left_basic` / signs)  
3. **Japanese question:** 車両は、道路の中央から左の部分（左側部分）を通行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第17条第4項は左側部分通行の原則を定めています。一方通行や追越し等の例外は同条第5項にあります。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第17条第4項  
8. **Conditions/exceptions:** 第17条第5項各号の例外（一方通行、幅員不足、追越し等）。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional include “原則として” in stem.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:33

1. **ID:** `menkyo_original_karimen_expansion_001:33`  
2. **Topic:** 歩道等進入時の一時停止 (`sidewalk_entry_stop` / pedestrian)  
3. **Japanese question:** 車両は、道路外の施設又は場所に出入するためやむを得ず歩道等を横断するときは、歩道等に入る直前で一時停止し、かつ、歩行者の通行を妨げないようにしなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第17条第2項は、歩道等進入時の一時停止と歩行者妨害禁止を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第17条第2項  
8. **Conditions/exceptions:** 第1項ただし書の進入事由が前提。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** None.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:34

1. **ID:** `menkyo_original_karimen_expansion_001:34`  
2. **Topic:** 進行方向別の通行区分 (`designated_direction_lanes` / turning)  
3. **Japanese question:** 車両は、車両通行帯の設けられた道路において、道路標識等により交差点で進行する方向に関する通行の区分が指定されているときは、原則としてその通行の区分に従い当該車両通行帯を通行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第35条第1項は、進行方向別通行区分の指定があるときの通行義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第35条第1項  
8. **Conditions/exceptions:** 緊急自動車対応・障害等のただし書。特定小型原動機付自転車等の除外。  
9. **Research assessment:** `supported_with_clarification`  
10. **Human-review questions:** Simplify exclusions for beginners?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:35

1. **ID:** `menkyo_original_karimen_expansion_001:35`  
2. **Topic:** 踏切内で動けなくなったとき (`railroad_stuck_measures` / stopping)  
3. **Japanese question:** 車両等の運転者は、故障その他の理由により踏切において当該車両等を運転することができなくなつたときは、直ちに非常信号を行う等踏切に故障車両等があることを知らせるための措置を講ずるとともに、当該車両等を踏切以外の場所に移動するため必要な措置を講じなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第33条第3項は、踏切内で運転不能となったときの非常信号等と移動措置を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第33条第3項  
8. **Conditions/exceptions:** 第1項・第2項の進入・停止義務は別命題。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Accept near-statutory educational restatement?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:36

1. **ID:** `menkyo_original_karimen_expansion_001:36`  
2. **Topic:** 消火栓付近の駐車禁止 (`no_park_fire_hydrant` / stopping)  
3. **Japanese question:** 車両は、消火栓から五メートル以内の部分においては、警察署長の許可を受けたときを除き、駐車してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第45条第1項第4号は、消火栓等から五メートル以内の駐車を禁止しています（警察署長許可の例外あり）。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第45条第1項第4号  
8. **Conditions/exceptions:** 柱書の警察署長許可。停車禁止との区別（第44条）は別。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional broaden stem to match full item 4 list.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:37

1. **ID:** `menkyo_original_karimen_expansion_001:37`  
2. **Topic:** 自動車の右折方法 (`right_turn_auto_method` / turning)  
3. **Japanese question:** 自動車は、右折するときは、あらかじめその前からできる限り道路の中央に寄り、かつ、交差点の中心の直近の内側（道路標識等により通行すべき部分が指定されているときはその指定された部分）を徐行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第34条第2項は、自動車等の右折方法（中央に寄り、中心の直近の内側を徐行）を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第34条第2項  
8. **Conditions/exceptions:** 一方通行の右折は第4項。特定小型原動機付自転車等は第3項。環状交差点は第35条の2。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm limiting the actor to 自動車 is acceptable teaching focus.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:38

1. **ID:** `menkyo_original_karimen_expansion_001:38`  
2. **Topic:** みだりな進路変更の禁止 (`no_needless_lane_change` / turning)  
3. **Japanese question:** 車両は、みだりにその進路を変更してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第26条の2第1項は、みだりな進路変更を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第26条の2第1項  
8. **Conditions/exceptions:** 後方車両への急な変更を生じさせる進路変更の禁止は第2項。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional add example in explanation only.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:39

1. **ID:** `menkyo_original_karimen_expansion_001:39`  
2. **Topic:** 急ブレーキの禁止 (`no_sudden_brake` / speed)  
3. **Japanese question:** 車両等の運転者は、危険を防止するためやむを得ない場合を除き、その車両等を急に停止させ、又はその速度を急激に減ずることとなるような急ブレーキをかけてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第24条は、危険防止のためやむを得ない場合を除き急ブレーキを禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第24条  
8. **Conditions/exceptions:** 危険防止の例外を設問に含む。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** None.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:40

1. **ID:** `menkyo_original_karimen_expansion_001:40`  
2. **Topic:** 最高速度は目標速度ではない (`posted_max_not_target` / speed)  
3. **Japanese question:** 道路標識等により最高速度が指定されている道路では、交通の流れを乱さないためにその最高速度で走行し続けることが求められている。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第22条第1項は最高速度を超えて進行してはならないとする上限規制です。状況に応じた安全な速度は第70条の安全運転義務でも求められます。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第22条第1項・第70条  
8. **Conditions/exceptions:** 標識のない道路の政令速度（施行令第11条）は本問対象外。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Do not expand into unsigned 30/60 numerical claims.  
11. **Publication status:** unpublished  

## Batch D

### menkyo_original_karimen_expansion_001:41

1. **ID:** `menkyo_original_karimen_expansion_001:41`  
2. **Topic:** 天候無視の速度誤解 (`safe_driving_weather_myth` / speed)  
3. **Japanese question:** 最高速度の指定がある道路では、雨や視界不良であってもその最高速度まで出して進行してよい。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。最高速度は上限であり、道路交通法第70条は状況に応じ他人に危害を及ぼさない速度と方法で運転する義務を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第70条・第22条第1項  
8. **Conditions/exceptions:** 標識指定最高速度の遵守は別義務。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm distinctness from pilot :7.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:42

1. **ID:** `menkyo_original_karimen_expansion_001:42`  
2. **Topic:** 車間距離の保持 (`following_distance` / speed)  
3. **Japanese question:** 車両等は、同一の進路を進行している他の車両等の直後を進行するときは、その直前の車両等が急に停止したときにおいてもこれに追突するのを避けることができるため必要な距離を保たなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第26条は必要な車間距離の保持を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第26条  
8. **Conditions/exceptions:** 具体的メートル数は条文に固定されていない。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Do not invent a numeric following-distance rule.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:43

1. **ID:** `menkyo_original_karimen_expansion_001:43`  
2. **Topic:** 割込み等の禁止 (`no_cutting_in` / passing)  
3. **Japanese question:** 車両は、停止し、若しくは停止しようとして徐行している車両等に追いついたときは、その前方にある車両等の側方を通過して当該車両等の前方に割り込み、又はその前方を横切つてはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第32条は、停止・徐行中の車両等への割込み・横切を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第32条  
8. **Conditions/exceptions:** 法令・警察官命令・危険防止のための停止等を含む柱書。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional include “法令の規定若しくは警察官の命令により” in stem.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:44

1. **ID:** `menkyo_original_karimen_expansion_001:44`  
2. **Topic:** 緊急自動車の優先 (`emergency_vehicle_yield` / other)  
3. **Japanese question:** 交差点又はその附近において緊急自動車が接近してきたときは、車両（緊急自動車を除く。）は交差点を避け、かつ、道路の左側（一方通行で左側に寄ると緊急自動車の通行を妨げることとなる場合は右側）に寄つて一時停止しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第40条第1項は、交差点付近での緊急自動車接近時の回避・左側（場合により右側）寄り・一時停止を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第40条第1項  
8. **Conditions/exceptions:** 交差点以外の場所は第40条第2項。路面電車の扱いも同項。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Accept length for accuracy?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:45

1. **ID:** `menkyo_original_karimen_expansion_001:45`  
2. **Topic:** 第一通行帯の通行 (`lane_keep_first` / signs)  
3. **Japanese question:** 車両は、車両通行帯の設けられた道路においては、道路の左側端から数えて一番目の車両通行帯を通行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第20条第1項本文は、原則として第一通行帯の通行を定めています。速度等に応じた例外はただし書にあります。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第20条第1項  
8. **Conditions/exceptions:** 自動車の三以上の通行帯におけるただし書。追越し等の第3項例外。標識による別区分は第2項。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Optional add “原則として”.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:46

1. **ID:** `menkyo_original_karimen_expansion_001:46`  
2. **Topic:** 道路外へ左折して出る方法 (`exit_road_left` / turning)  
3. **Japanese question:** 車両は、道路外に出るため左折するときは、あらかじめその前からできる限り道路の左側端に寄り、かつ、徐行しなければならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第25条第1項は、道路外へ出る左折時の寄る位置と徐行を定めています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第25条第1項  
8. **Conditions/exceptions:** 右折して出る場合は第2項。合図時の後方車両義務は第3項。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** None.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:47

1. **ID:** `menkyo_original_karimen_expansion_001:47`  
2. **Topic:** 横断歩道なしなら歩行者無視の誤解 (`no_crosswalk_no_priority_myth` / pedestrian)  
3. **Japanese question:** 交差点に横断歩道が設けられていない場合、歩行者が横断していても車両等はその通行を妨げてよい。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。道路交通法第38条の2は、横断歩道のない交差点等で横断中の歩行者の通行妨害を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第38条の2  
8. **Conditions/exceptions:** 横断歩道がある場合は第38条。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm distinctness from candidate 16.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:48

1. **ID:** `menkyo_original_karimen_expansion_001:48`  
2. **Topic:** 信号交差点でも左方優先との誤解 (`left_priority_with_signals_myth` / intersection)  
3. **Japanese question:** 交通整理が行われている交差点でも、道路交通法第36条第1項の左方優先の規定がそのまま適用される。  
4. **Proposed answer:** 誤り（False）  
5. **Explanation:** 誤りです。第36条第1項は「交通整理の行なわれていない交差点」を場面としており、信号等による交通整理がある交差点には同項の左方優先は適用されません。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第36条第1項  
8. **Conditions/exceptions:** 信号に従う義務は第7条。環状交差点は第37条の2。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Confirm teaching note on 交通整理.  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:49

1. **ID:** `menkyo_original_karimen_expansion_001:49`  
2. **Topic:** 走行中の携帯電話 (`no_handheld_phone` / other)  
3. **Japanese question:** 自動車を運転する場合においては、当該自動車が停止しているときを除き、その全部又は一部を手で保持しなければ送受信できない携帯電話用装置を通話のために使用してはならない（傷病者の救護又は公共の安全の維持のため走行中に緊急やむを得ず行う場合を除く）。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第71条第5号の5は、停止中を除き手持ち無線通話装置の通話使用等を禁じています（緊急やむを得ない通話の例外あり）。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第71条第5号の5  
8. **Conditions/exceptions:** 画像注視禁止も同号。ハンズフリー等の装置要件は条文の括弧定義に依存。  
9. **Research assessment:** `supported_with_clarification`  
10. **Human-review questions:** Simplify device definition for beginners while staying accurate?  
11. **Publication status:** unpublished  

### menkyo_original_karimen_expansion_001:50

1. **ID:** `menkyo_original_karimen_expansion_001:50`  
2. **Topic:** 酒気帯び運転の禁止 (`drunk_driving_ban` / other)  
3. **Japanese question:** 何人も、酒気を帯びて車両等を運転してはならない。  
4. **Proposed answer:** 正しい（True）  
5. **Explanation:** 道路交通法第65条第1項は、酒気を帯びての車両等の運転を禁じています。  
6. **Official source:** https://laws.e-gov.go.jp/law/335AC0000000105/  
7. **Exact provision:** 道路交通法 第65条第1項  
8. **Conditions/exceptions:** 提供・同乗等の関連禁止は第2項以下。数値基準の罰則区分は本問対象外。  
9. **Research assessment:** `supported_as_written`  
10. **Human-review questions:** Do not invent BAC numeric thresholds in the stem.  
11. **Publication status:** unpublished  

## Staging and gates

- Dry-run does not mutate records.
- `--commit` is required to write; idempotent by stable `source_group` + `original_question_number`.
- Human-protected rows are skipped.
- Staging never sets human accuracy / editorial / rights / answer-key / publication approvals.
- Full Karimen pool size requirements remain unchanged; demo exams remain available.

Do not treat this Markdown file as a reviewer signature.
