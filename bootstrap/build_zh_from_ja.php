<?php

declare(strict_types=1);

require __DIR__.'/export_array.php';

$ja = require dirname(__DIR__).'/lang/ja/road_signs.php';

/** @var array<string, string> $map */
$map = [
    '日本の道路標識を学ぶ | MenkyoNavi' => '学习日本道路标志 | MenkyoNavi',
    '道路標識' => '道路标志',
    '交通ルール学習ガイド' => '交通规则学习指南',
    '日本の道路標識を学ぶ' => '学习日本道路标志',
    '規制・警戒・案内・指示・補助標識の5分類と、学科試験で出やすい主要標識の意味・行動を学べる図解ライブラリ。MenkyoNaviは政府機関ではありません。' => '管制·警告·指引·指示·辅助五类标志及常见考试标志含义与行动的学习图库。MenkyoNavi 非政府机构。',
    '最終確認日' => '最后核对',
    '読了目安：約18分' => '阅读约 18 分钟',
    '目次' => '目录',
    'はじめに' => '简介',
    '5つの分類' => '五类划分',
    '標識ライブラリ' => '标志库',
    '標識の詳細' => '标志详情',
    '場面別の読み方' => '按场景阅读',
    '理解確認（学習用）' => '理解自测（学习用）',
    'よくある誤解' => '常见误解',
    'よくある質問' => '常见问题',
    '参考情報' => '参考来源',
    '関連学習' => '相关学习',
    '日本の道路標識：5つの分類' => '日本道路标志：五类划分',
    '補助標識は単独で読まない' => '不要单独阅读辅助标志',
    '理解確認クイズ（学習用・非公式）' => '自测小测验（非官方）',
    '教育用練習' => '学习练习',
    '参考にした公式情報' => '参考的官方资料',
    '試験コースで総合練習' => '浏览考试练习',
    '仮免学科の学習ガイド' => '临时驾照学科指南',
    '本免学科の学習ガイド' => '正式驾照学科指南',
];

$data = translateTree($ja, $map);
$data['draft_notice'] = '本译文为草稿，尚待母语使用者审阅。';

file_put_contents(dirname(__DIR__).'/lang/zh/road_signs.php', "<?php\n\nreturn ".exportArray($data).";\n");
echo "Built zh from ja\n";

/** @param array<string, mixed> $node @param array<string, string> $map */
function translateTree(array $node, array $map): array
{
    $out = [];
    foreach ($node as $key => $value) {
        if (is_array($value)) {
            $out[$key] = translateTree($value, $map);
        } elseif (is_string($value)) {
            $out[$key] = $map[$value] ?? $value;
        } else {
            $out[$key] = $value;
        }
    }

    return $out;
}
