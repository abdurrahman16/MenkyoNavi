<?php

/**
 * Task 47 controlled Japanese source-revision proposals for withheld pilot candidates.
 *
 * These are research-supported proposals only. They are NOT human accuracy approvals,
 * editorial approvals, rights clearances, or publication decisions.
 * Applying a proposal requires an authenticated administrator action in the workbench.
 *
 * @return array{
 *     collection_key: string,
 *     prepared_at: string,
 *     sources_accessed_at: string,
 *     law_version_note: string,
 *     sources_inspected: list<array{label: string, url: string, accessed_at: string}>,
 *     proposals: array<int, array<string, mixed>>,
 *     human_revision_decisions_recorded: int
 * }
 */
return [
    'collection_key' => 'menkyo_original_karimen_pilot_001',
    'prepared_at' => '2026-10-11',
    'sources_accessed_at' => '2026-10-11',
    'law_version_note' => '道路交通法 text inspected via e-Gov laws.e-gov.go.jp/law/335AC0000000105/ (access 2026-10-11). Art. 7 and Art. 36 operative wording confirmed; no material effective-date conflict identified for these two propositions. Unsigned-road 施行令第11条 speed topic remains excluded.',
    'sources_inspected' => [
        [
            'label' => '道路交通法（e-Gov）',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'accessed_at' => '2026-10-11',
        ],
        [
            'label' => '道路交通法 第7条・第36条（e-Gov 本文照合）',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'accessed_at' => '2026-10-11',
        ],
    ],
    'human_revision_decisions_recorded' => 0,
    'proposals' => [
        1 => [
            'candidate_id' => 'menkyo_original_karimen_pilot_001:1',
            'number' => 1,
            'issue_summary' => '第7条の義務主体は「歩行者等又は車両等」。現行設問は車両等のみを述べており命題としては支持されるが、学習者が主体スコープを狭く理解し得るため、条文主体に揃えた設問への改訂を提案する（横断歩道・第38条の問題ではない）。',
            'research_concern' => 'Subject-scope clarification under Road Traffic Act Art. 7 (歩行者等又は車両等). Historical workbench label “横断歩道スコープ” referred to pedestrian subject scope for signals, not Art. 38 crosswalk priority.',
            'exact_provision' => '道路交通法 第7条（信号機の信号等に従う義務）',
            'statute_excerpt' => '道路を通行する歩行者等又は車両等は、信号機の表示する信号又は警察官等の手信号等（前条第一項後段の場合においては、当該手信号等）に従わなければならない。',
            'source_url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'current_question_ja' => '道路を通行する車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。',
            'current_explanation_ja' => '道路交通法第7条は、道路を通行する歩行者等又は車両等が信号機の表示する信号又は警察官等の手信号等に従う義務を定めています。本問の「車両等」についてもこの義務が当てはまり、信号に従わない進行は許されません。',
            'current_correct_answer' => true,
            'proposed_question_ja' => '道路を通行する歩行者等又は車両等は、信号機の表示する信号又は警察官等の手信号等に従わなければならない。',
            'proposed_explanation_ja' => '道路交通法第7条は、道路を通行する歩行者等又は車両等が信号機の表示する信号又は警察官等の手信号等に従う義務を定めています。設問はこの法定義務の主体と内容を問うものであり、信号に従わない進行は許されません。',
            'proposed_correct_answer' => true,
            'answer_changes' => false,
            'why_revision_improves_accuracy' => '設問主体を第7条本文の「歩行者等又は車両等」に揃えることで、車両限定の誤読を防ぎ、○×命題を条文と一対一で対応させる。',
            'remaining_ambiguity' => '警察官等の手信号等が優先される場面（第6条第1項後段）は本問の対象外。個別信号色の意味も対象外。',
            'translation_impact' => '改訂適用後に初めて翻訳起草対象になり得る。既存56件の他候補ドラフトには影響しない。改訂適用前は翻訳保留のまま。',
            'human_revision_decision_status' => 'pending_human_decision',
            'research_supported' => true,
        ],
        8 => [
            'candidate_id' => 'menkyo_original_karimen_pilot_001:8',
            'number' => 8,
            'issue_summary' => '第36条第1項第1号は、車両について左方からの車両に加え交差道路の路面電車の進行妨害禁止を定める。現行設問は左方の「車両等」中心で路面電車条項が明示されず、主体も「車両等」と一般化し過ぎる。',
            'research_concern' => 'Art. 36(1)(i) streetcar qualification and vehicle-subject wording; Art. 36(2) priority/wider-road carve-out must remain; not a universal left-priority rule.',
            'exact_provision' => '道路交通法 第36条（交差点における他の車両等との関係等）第1項第1号・第2項',
            'statute_excerpt' => '車両等は、交通整理の行なわれていない交差点においては、次項の規定が適用される場合を除き…一 車両である場合 交差道路を左方から進行してくる車両及び交差道路を通行する路面電車…２ …交差道路が優先道路であるとき、又は…幅員が明らかに広いものであるときは、当該交差道路を通行する車両等の進行妨害をしてはならない。',
            'source_url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'current_question_ja' => '交通整理の行われていない交差点においては、道路交通法第36条第2項が適用される場合を除き、交差道路を左方から進行してくる車両等の進行妨害をしてはならない。',
            'current_explanation_ja' => 'いわゆる左方優先です。第36条第1項第1号は、車両について、左方からの車両に加え交差道路の路面電車の進行妨害も禁じています。ただし同条第2項により、交差道路が優先道路であるときや幅員が明らかに広いときは、その交差道路を通行する車両等の進行妨害をしてはなりません。設問は左方車両を中心に述べており、路面電車条項は説明で補います（Task 44監査）。',
            'current_correct_answer' => true,
            'proposed_question_ja' => '交通整理の行われていない交差点においては、道路交通法第36条第2項が適用される場合を除き、車両は、交差道路を左方から進行してくる車両及び交差道路を通行する路面電車の進行妨害をしてはならない。',
            'proposed_explanation_ja' => 'いわゆる左方優先です。第36条第1項第1号は、車両について、左方からの車両に加え交差道路を通行する路面電車の進行妨害も禁じています。ただし同条第2項により、交差道路が優先道路であるときや幅員が明らかに広いときは、その交差道路を通行する車両等の進行妨害をしてはなりません。本問は車両側の義務に焦点を当て、路面電車が主体となる場合（同項第2号）は対象外です。',
            'proposed_correct_answer' => true,
            'answer_changes' => false,
            'why_revision_improves_accuracy' => '主体を「車両」に特定し、第1項第1号の路面電車条項を設問本文へ明示する。第2項除外を維持し、絶対的左方優先の誤解を避ける。',
            'remaining_ambiguity' => '環状交差点（第37条の2）は別則。路面電車が主体の場合は第1項第2号。交通整理のある交差点は本条の場面外。',
            'translation_impact' => '改訂適用後に初めて翻訳起草対象になり得る。既存56件には影響しない。提案文を現行文の翻訳として用いてはならない。',
            'human_revision_decision_status' => 'pending_human_decision',
            'research_supported' => true,
            'replacement_recommended_if_unresolvable' => false,
        ],
    ],
];
