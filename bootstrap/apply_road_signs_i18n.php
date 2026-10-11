<?php

declare(strict_types=1);

$draftNotices = [
    'ko' => '이 번역은 원어민 검토를 기다리는 초안입니다.',
    'zh' => '本译文为草稿，尚待母语使用者审阅。',
    'vi' => 'Bản dịch này là bản nháp, đang chờ người bản ngữ rà soát.',
    'fil' => 'Ang salin na ito ay draft at naghihintay ng pagsusuri mula sa isang katutubong tagapagsalita.',
    'ne' => 'यो अनुवाद मस्यौदा हो र मातृभाषी समीक्षाको प्रतीक्षामा छ।',
    'bn' => 'এই অনুবাদটি খসড়া; মাতৃভাষী পর্যালোচকারির অপেক্ষায় রয়েছে。',
];

$titles = [
    'ko' => ['일본 도로표지 배우기 | MenkyoNavi', '일본 도로표지 배우기'],
    'zh' => ['学习日本道路标志 | MenkyoNavi', '学习日本道路标志'],
    'vi' => ['Học biển báo đường bộ Nhật Bản | MenkyoNavi', 'Học biển báo đường bộ Nhật Bản'],
    'fil' => ['Alamin ang mga karatula sa kalsada sa Japan | MenkyoNavi', 'Alamin ang mga karatula sa kalsada sa Japan'],
    'ne' => ['जापानी सडक चिन्ह सिक्नुहोस् | MenkyoNavi', 'जापानी सडक चिन्ह सिक्नुहोस्'],
    'bn' => ['জাপানি সড়ক চিহ্ন শিখুন | MenkyoNavi', 'জাপানি সড়ক চিহ্ন শিখুন'],
];

foreach (array_keys($draftNotices) as $locale) {
    $mapPath = __DIR__."/road_signs_i18n/{$locale}.php";
    if (! is_file($mapPath)) {
        echo "Missing map {$locale}\n";

        continue;
    }
    /** @var array<string, string> $map */
    $map = require $mapPath;
    $content = file_get_contents(dirname(__DIR__).'/lang/en/road_signs.php');
    uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($map as $from => $to) {
        $content = str_replace(var_export($from, true), var_export($to, true), $content);
    }
    [$metaTitle, $title] = $titles[$locale];
    $content = str_replace(var_export('Learn Japanese Road Signs | MenkyoNavi', true), var_export($metaTitle, true), $content);
    $content = str_replace(var_export('Learn Japanese Road Signs', true), var_export($title, true), $content);
    $content = str_replace(
        var_export('This translation is a draft pending review by a native speaker.', true),
        var_export($draftNotices[$locale], true),
        $content
    );
    file_put_contents(dirname(__DIR__)."/lang/{$locale}/road_signs.php", $content);
    echo "Applied {$locale}\n";
}
