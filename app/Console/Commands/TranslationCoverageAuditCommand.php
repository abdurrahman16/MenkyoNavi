<?php

namespace App\Console\Commands;

use App\Support\SupportedLocale;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class TranslationCoverageAuditCommand extends Command
{
    protected $signature = 'menkyo:translation-coverage
        {--json : Output machine-readable JSON}
        {--path= : Optional report file path}';

    protected $description = 'Audit UI translation coverage across supported locales (does not claim verified translation quality).';

    public function handle(): int
    {
        $referenceLocale = 'en';
        $groups = $this->discoverGroups($referenceLocale);
        $locales = SupportedLocale::codes();
        $hardcodedHints = $this->scanHardcodedEnglishHints();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'reference_locale' => $referenceLocale,
            'note' => 'Automated key coverage only. Does not certify translation quality or regulatory accuracy.',
            'locales' => [],
            'hardcoded_english_hints' => $hardcodedHints,
        ];

        foreach ($locales as $locale) {
            $missing = [];
            $empty = [];
            $identicalToEnglish = [];

            foreach ($groups as $group) {
                $reference = Arr::dot(trans($group, [], $referenceLocale));
                $translated = Arr::dot(trans($group, [], $locale));

                foreach ($reference as $key => $englishValue) {
                    $fullKey = $group.'.'.$key;

                    if (! array_key_exists($key, $translated)) {
                        $missing[] = $fullKey;

                        continue;
                    }

                    $value = $translated[$key];

                    if (! is_string($value) || trim($value) === '') {
                        $empty[] = $fullKey;

                        continue;
                    }

                    if (
                        $locale !== $referenceLocale
                        && is_string($englishValue)
                        && $value === $englishValue
                        && ! $this->isIntentionallyShared($value)
                    ) {
                        $identicalToEnglish[] = $fullKey;
                    }
                }
            }

            $report['locales'][$locale] = [
                'missing_keys' => $missing,
                'empty_keys' => $empty,
                'identical_to_english_keys' => $identicalToEnglish,
                'missing_count' => count($missing),
                'empty_count' => count($empty),
                'identical_to_english_count' => count($identicalToEnglish),
            ];
        }

        if ($path = $this->option('path')) {
            file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Wrote report to {$path}");
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('UI translation coverage audit (automated; not a quality certification)');
        $this->newLine();

        $rows = [];

        foreach ($report['locales'] as $locale => $data) {
            $rows[] = [
                $locale,
                (string) $data['missing_count'],
                (string) $data['empty_count'],
                (string) $data['identical_to_english_count'],
            ];
        }

        $this->table(['Locale', 'Missing', 'Empty', 'Identical to EN'], $rows);
        $this->newLine();
        $this->line('Hardcoded English hints in Blade (heuristic): '.count($hardcodedHints));

        foreach (array_slice($hardcodedHints, 0, 20) as $hint) {
            $this->line(' - '.$hint);
        }

        if (count($hardcodedHints) > 20) {
            $this->line(' … and '.(count($hardcodedHints) - 20).' more');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function discoverGroups(string $locale): array
    {
        $directory = lang_path($locale);

        if (! is_dir($directory)) {
            return [];
        }

        $groups = [];

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
            $groups[] = basename($file, '.php');
        }

        sort($groups);

        return $groups;
    }

    private function isIntentionallyShared(string $value): bool
    {
        $shared = ['MenkyoNavi', '免許ナビ', '外免切替', '仮免', '本免', 'ID', 'FAQ', 'Cookie'];

        return in_array($value, $shared, true) || strlen($value) <= 3;
    }

    /**
     * @return list<string>
     */
    private function scanHardcodedEnglishHints(): array
    {
        $roots = [
            resource_path('views/home'),
            resource_path('views/exams'),
            resource_path('views/learn'),
            resource_path('views/guides'),
            resource_path('views/pages'),
            resource_path('views/legal'),
            resource_path('views/contact'),
            resource_path('views/livewire/admin'),
            resource_path('views/components/ui'),
            resource_path('views/components/site'),
            resource_path('views/components/layouts'),
        ];

        $patterns = [
            'How it works',
            'Choose your Japanese',
            'Learning resources',
            'Ready to start',
            'Question bank dashboard',
            'Browse questions',
            'Contact us',
            'Privacy policy',
            'Explore →',
            'Independent educational resource',
            'Sample learning interface',
            'Side-by-side',
        ];

        $hits = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)),
                '/\.blade\.php$/i'
            );

            foreach ($iterator as $file) {
                $contents = file_get_contents($file->getPathname()) ?: '';

                foreach ($patterns as $pattern) {
                    if (str_contains($contents, $pattern)) {
                        $hits[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).' :: '.$pattern;
                    }
                }
            }
        }

        sort($hits);

        return $hits;
    }
}
