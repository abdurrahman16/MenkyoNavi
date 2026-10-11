<?php

/**
 * Task 49 controlled Japanese source-revision proposals for expansion clarification candidates.
 *
 * Research-supported proposals only. NOT human accuracy / editorial / rights / publication approvals.
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
    'collection_key' => 'menkyo_original_karimen_expansion_001',
    'prepared_at' => '2026-10-11',
    'sources_accessed_at' => '2026-10-11',
    'law_version_note' => '道路交通法 text inspected via e-Gov laws.e-gov.go.jp/law/335AC0000000105/ (access 2026-10-11). Art. 35(1), Art. 36(2), and Art. 71(v)-5 operative wording confirmed for these three propositions. Unsigned-road 施行令第11条 numerical speed claims remain excluded.',
    'sources_inspected' => [
        [
            'label' => '道路交通法（e-Gov）',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'accessed_at' => '2026-10-11',
        ],
        [
            'label' => '道路交通法 第35条・第36条・第71条（e-Gov 本文照合）',
            'url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'accessed_at' => '2026-10-11',
        ],
    ],
    'human_revision_decisions_recorded' => 0,
    'proposals' => [
        26 => [
            'candidate_id' => 'menkyo_original_karimen_expansion_001:26',
            'number' => 26,
            'issue_summary' => '第36条第2項は「その通行している道路が優先道路である場合を除き」と本文で除外を置く。現行設問は括弧で冗長に説明しており、初心者向け命題として読みにくい。条文本文の順序に揃えた簡潔な設問へ改訂を提案する。',
            'research_concern' => 'Art. 36(2) priority-road yield: remove parenthetical meta-commentary; keep the statutory carve-out that the duty does not apply when the driver’s own road is a priority road; wider intersecting road also triggers the duty (cover in explanation).',
            'exact_provision' => '道路交通法 第36条第2項',
            'statute_excerpt' => '車両等は、交通整理の行なわれていない交差点においては、その通行している道路が優先道路（道路標識等により優先道路として指定されているもの及び当該交差点において当該道路における車両の通行を規制する道路標識等による中央線又は車両通行帯が設けられている道路をいう。以下同じ。）である場合を除き、交差道路が優先道路であるとき、又はその通行している道路の幅員よりも交差道路の幅員が明らかに広いものであるときは、当該交差道路を通行する車両等の進行妨害をしてはならない。',
            'source_url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'current_question_ja' => '車両等は、交通整理の行われていない交差点において、交差道路が優先道路であるときは、当該交差道路を通行する車両等の進行妨害をしてはならない（自らが優先道路を通行している場合を除く趣旨の第36条第2項本文の枠内）。',
            'current_explanation_ja' => '道路交通法第36条第2項は、自らの道路が優先道路である場合を除き、交差道路が優先道路等のときにその車両等の進行妨害を禁じています。',
            'current_correct_answer' => true,
            'proposed_question_ja' => '車両等は、交通整理の行われていない交差点においては、その通行している道路が優先道路である場合を除き、交差道路が優先道路であるときは、当該交差道路を通行する車両等の進行妨害をしてはならない。',
            'proposed_explanation_ja' => '道路交通法第36条第2項は、自らが通行している道路が優先道路である場合を除き、交差道路が優先道路であるとき（又は交差道路の幅員が明らかに広いとき）は、その交差道路を通行する車両等の進行妨害をしてはならないと定めています。本問は優先道路側への進行妨害禁止に焦点を当て、幅員が明らかに広い場合は説明で補います。',
            'proposed_correct_answer' => true,
            'answer_changes' => false,
            'why_revision_improves_accuracy' => 'メタ説明の括弧をやめ、第36条第2項本文の除外（自らの道路が優先道路である場合）を設問本文に明示する。命題は条文と一対一で対応しやすくなる。',
            'remaining_ambiguity' => '「明らかに広い交差道路」も同項の別条件。優先道路の定義（標識指定・中央線等）は括弧書。交通整理のある交差点は場面外。',
            'translation_impact' => '改訂適用後に初めて翻訳起草対象になり得る。既存56件のパイロット翻訳ドラフトには影響しない。',
            'human_revision_decision_status' => 'pending_human_decision',
            'research_supported' => true,
        ],
        34 => [
            'candidate_id' => 'menkyo_original_karimen_expansion_001:34',
            'number' => 34,
            'issue_summary' => '第35条第1項は指定通行区分に従う義務を定め、ただし書で緊急自動車対応・障害等のやむを得ない場合を除外する。現行設問の「原則として」は条文にない柔らかい表現で、除外主体（特定小型原動機付自転車等）も設問に未反映。条文に近い簡潔設問へ改訂を提案する。',
            'research_concern' => 'Art. 35(1) designated direction lanes: drop non-statutory「原則として」; keep duty as written; place ただし書 and subject carve-outs in explanation.',
            'exact_provision' => '道路交通法 第35条第1項',
            'statute_excerpt' => '車両（特定小型原動機付自転車等及び右折につき一般原動機付自転車が前条第五項本文の規定によることとされる交差点において左折又は右折をする一般原動機付自転車を除く。）は、車両通行帯の設けられた道路において、道路標識等により交差点で進行する方向に関する通行の区分が指定されているときは、同条第一項、第二項及び第四項の規定にかかわらず、当該通行の区分に従い当該車両通行帯を通行しなければならない。ただし、第四十条の規定に従うため、又は道路の損壊、道路工事その他の障害のためやむを得ないときは、この限りでない。',
            'source_url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'current_question_ja' => '車両は、車両通行帯の設けられた道路において、道路標識等により交差点で進行する方向に関する通行の区分が指定されているときは、原則としてその通行の区分に従い当該車両通行帯を通行しなければならない。',
            'current_explanation_ja' => '道路交通法第35条第1項は、進行方向別通行区分の指定があるときの通行義務を定めています。',
            'current_correct_answer' => true,
            'proposed_question_ja' => '車両は、車両通行帯の設けられた道路において、道路標識等により交差点で進行する方向に関する通行の区分が指定されているときは、当該通行の区分に従い当該車両通行帯を通行しなければならない。',
            'proposed_explanation_ja' => '道路交通法第35条第1項は、進行方向別の通行区分が指定されているときの車両の通行義務を定めています。ただし、緊急自動車の優先に従うため、又は道路の損壊・工事その他の障害のためやむを得ないときはこの限りではありません。特定小型原動機付自転車等の一部主体は同項の対象外です。',
            'proposed_correct_answer' => true,
            'answer_changes' => false,
            'why_revision_improves_accuracy' => '条文にない「原則として」を除き、義務命題を第35条第1項本文に揃える。ただし書と除外主体は解説で明示し、設問の曖昧さを減らす。',
            'remaining_ambiguity' => '一般原動機付自転車の右折特例除外は初心者向けには重い。第34条との関係は本問の焦点外。',
            'translation_impact' => '改訂適用後に初めて翻訳起草対象になり得る。既存56件には影響しない。',
            'human_revision_decision_status' => 'pending_human_decision',
            'research_supported' => true,
        ],
        49 => [
            'candidate_id' => 'menkyo_original_karimen_expansion_001:49',
            'number' => 49,
            'issue_summary' => '第71条第5号の5の義務主体は「自動車、原動機付自転車又は自転車」。現行設問は「自動車」のみで狭く、装置定義も「送受信できない」と条文（送信及び受信のいずれをも行うことができない）からずれている。条文に揃えた設問へ改訂を提案する（画像注視は本問の対象外として説明で注記）。',
            'research_concern' => 'Art. 71(v)-5 handheld wireless call ban: align subject (自動車等) and device definition with statute; keep emergency call carve-out; image-display gazing is separate limb of same item.',
            'exact_provision' => '道路交通法 第71条第5号の5',
            'statute_excerpt' => '自動車、原動機付自転車又は自転車（以下この号において「自動車等」という。）を運転する場合においては、当該自動車等が停止しているときを除き、携帯電話用装置、自動車電話用装置その他の無線通話装置（その全部又は一部を手で保持しなければ送信及び受信のいずれをも行うことができないものに限る。…）を通話（傷病者の救護又は公共の安全の維持のため当該自動車等の走行中に緊急やむを得ずに行うものを除く。…）のために使用し、又は…画像表示用装置…に表示された画像を注視しないこと。',
            'source_url' => 'https://laws.e-gov.go.jp/law/335AC0000000105/',
            'current_question_ja' => '自動車を運転する場合においては、当該自動車が停止しているときを除き、その全部又は一部を手で保持しなければ送受信できない携帯電話用装置を通話のために使用してはならない（傷病者の救護又は公共の安全の維持のため走行中に緊急やむを得ず行う場合を除く）。',
            'current_explanation_ja' => '道路交通法第71条第5号の5は、停止中を除き手持ち無線通話装置の通話使用等を禁じています（緊急やむを得ない通話の例外あり）。',
            'current_correct_answer' => true,
            'proposed_question_ja' => '自動車、原動機付自転車又は自転車を運転する場合においては、当該自動車等が停止しているときを除き、その全部又は一部を手で保持しなければ送信及び受信のいずれをも行うことができない無線通話装置を通話のために使用してはならない（傷病者の救護又は公共の安全の維持のため走行中に緊急やむを得ず行う場合を除く）。',
            'proposed_explanation_ja' => '道路交通法第71条第5号の5は、自動車・原動機付自転車・自転車について、停止中を除き、手持ちでなければ送受信できない無線通話装置を通話に使うことを禁じています（傷病者の救護又は公共の安全の維持のための緊急やむを得ない通話を除く）。同号は画像表示用装置の注視も禁じますが、本問は通話使用に焦点を当てています。',
            'proposed_correct_answer' => true,
            'answer_changes' => false,
            'why_revision_improves_accuracy' => '義務主体を条文どおり自動車等に広げ、装置定義を「送信及び受信のいずれをも」に揃える。緊急通話の例外は維持する。',
            'remaining_ambiguity' => '画像注視肢は同号の別行為。自動運行装置使用時の適用除外（第71条の4の2等）は本問の対象外。装置の技術定義は括弧書に依存。',
            'translation_impact' => '改訂適用後に初めて翻訳起草対象になり得る。既存56件には影響しない。',
            'human_revision_decision_status' => 'pending_human_decision',
            'research_supported' => true,
        ],
    ],
];
