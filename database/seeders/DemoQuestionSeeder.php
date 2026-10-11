<?php

namespace Database\Seeders;

use App\Enums\ContentReviewStatus;
use App\Enums\CopyrightReuseStatus;
use App\Enums\QuestionMediaType;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceType;
use App\Enums\TranslationReviewStatus;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionMedia;
use App\Models\QuestionStatement;
use App\Models\QuestionStatementTranslation;
use App\Models\QuestionTranslation;
use App\Models\SourceReference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $source = SourceReference::query()->updateOrCreate(
            ['title' => 'MenkyoNavi Internal Demo Question Set'],
            [
                'issuing_organization' => 'MenkyoNavi',
                'source_type' => SourceType::InternalDemo,
                'original_url' => null,
                'edition_or_publication_date' => '2026-demo',
                'page_number' => null,
                'last_verified_at' => null,
                'copyright_reuse_status' => CopyrightReuseStatus::EducationalDemo,
                'notes' => 'Original demonstration content for development only. Not official exam questions and not officially verified.',
            ]
        );

        $categories = QuestionCategory::query()->pluck('id', 'slug');
        $examTypes = ExamType::query()->pluck('id', 'slug');

        foreach ($this->standardQuestions() as $item) {
            $this->seedStandardQuestion($item, $categories, $examTypes, $source->id);
        }

        $this->seedIllustratedGroup($categories, $examTypes, $source->id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function standardQuestions(): array
    {
        return [
            [
                'uuid' => '11111111-1111-4111-8111-111111111101',
                'category' => 'road-signs',
                'exams' => ['gaimen-kirikae', 'karimen', 'honmen'],
                'ja' => '一時停止の標識がある場所では、必ず完全に停止しなければならない。',
                'en' => 'Where there is a stop sign, you must come to a complete stop.',
                'answer' => true,
                'explain_en' => 'A rolling pause is not enough at a stop sign.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111102',
                'category' => 'traffic-signals',
                'exams' => ['gaimen-kirikae', 'karimen', 'honmen'],
                'ja' => '信号が赤のときは、交差点に進入してもよい。',
                'en' => 'When the traffic light is red, you may enter the intersection.',
                'answer' => false,
                'explain_en' => 'Red means stop; do not enter unless a specific legal exception applies.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111103',
                'category' => 'pedestrian-safety',
                'exams' => ['gaimen-kirikae', 'karimen', 'honmen'],
                'ja' => '横断歩道を渡っている歩行者がいるときは、一時停止して進路を譲らなければならない。',
                'en' => 'If pedestrians are crossing at a crosswalk, you must stop and yield.',
                'answer' => true,
                'explain_en' => 'Pedestrian priority at crosswalks is a core safety rule.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111104',
                'category' => 'speed-limits',
                'exams' => ['karimen', 'honmen'],
                'ja' => '制限速度の標識がある道路では、その速度を超えて運転してもよい。',
                'en' => 'Where a speed-limit sign is posted, you may drive faster than that limit.',
                'answer' => false,
                'explain_en' => 'Posted limits are maximums under normal conditions.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111105',
                'category' => 'right-of-way',
                'exams' => ['gaimen-kirikae', 'honmen'],
                'ja' => '優先道路を走行している車両は、交差する道路の車両より優先される。',
                'en' => 'Vehicles on a priority road generally have right of way over vehicles entering from intersecting roads.',
                'answer' => true,
                'explain_en' => 'Priority-road markings and signs change default intersection priority.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111106',
                'category' => 'railroad-crossings',
                'exams' => ['karimen', 'honmen'],
                'ja' => '踏切の直前では、安全を確認するために一時停止しなければならない。',
                'en' => 'Immediately before a railroad crossing, you must stop temporarily to confirm safety.',
                'answer' => true,
                'explain_en' => 'Stopping and checking at crossings is a standard requirement.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111107',
                'category' => 'parking-and-stopping',
                'exams' => ['gaimen-kirikae', 'karimen'],
                'ja' => '交差点の近くでは、交通の妨げになる駐車をしてもよい。',
                'en' => 'You may park near an intersection even if it obstructs traffic.',
                'answer' => false,
                'explain_en' => 'Parking that obstructs intersections or visibility is generally prohibited.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111108',
                'category' => 'emergency-vehicles',
                'exams' => ['gaimen-kirikae', 'honmen'],
                'ja' => '緊急自動車が接近してきたときは、道を譲らなければならない。',
                'en' => 'When an emergency vehicle approaches, you must yield the way.',
                'answer' => true,
                'explain_en' => 'Clear a path safely for emergency vehicles.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111109',
                'category' => 'expressways',
                'exams' => ['honmen'],
                'ja' => '高速道路では、本線車道に入る前に加速車線で十分に速度を上げる。',
                'en' => 'On expressways, you should use the acceleration lane to build enough speed before joining the main carriageway.',
                'answer' => true,
                'explain_en' => 'Matching traffic flow reduces merging conflicts.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111110',
                'category' => 'vehicle-safety',
                'exams' => ['karimen', 'honmen'],
                'ja' => 'シートベルトは、運転者だけが着用すれば十分である。',
                'en' => 'It is enough if only the driver wears a seat belt.',
                'answer' => false,
                'explain_en' => 'Seat-belt requirements generally apply to passengers as well where mandated.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111111',
                'category' => 'road-markings',
                'exams' => ['gaimen-kirikae', 'karimen', 'honmen'],
                'ja' => '黄色い実線は、原則としてはみ出して追い越しをしてはならないことを示す。',
                'en' => 'A solid yellow center line generally means you must not cross it to overtake.',
                'answer' => true,
                'explain_en' => 'Solid center lines restrict crossing/overtaking in many situations.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111112',
                'category' => 'intersections',
                'exams' => ['karimen', 'honmen'],
                'ja' => '右折するときは、あらかじめできるだけ道路の中央に寄り、徐行しなければならない。',
                'en' => 'When turning right, you should move toward the center of the road in advance and slow down.',
                'answer' => true,
                'explain_en' => 'Correct positioning and reduced speed improve right-turn safety.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111113',
                'category' => 'hazard-prediction',
                'exams' => ['honmen'],
                'ja' => '見通しの悪い交差点では、徐行して左右の安全を確認する必要がある。',
                'en' => 'At intersections with poor visibility, you need to slow down and check left and right for safety.',
                'answer' => true,
                'explain_en' => 'Reduced speed buys reaction time when sight lines are limited.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111114',
                'category' => 'driving-responsibilities',
                'exams' => ['gaimen-kirikae', 'karimen', 'honmen'],
                'ja' => '飲酒運転は、たとえ少しでも違法である。',
                'en' => 'Driving after drinking is illegal even in small amounts under Japan’s strict impaired-driving rules.',
                'answer' => true,
                'explain_en' => 'Japan enforces strict limits; never drive after drinking.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111115',
                'category' => 'pedestrian-safety',
                'exams' => ['karimen'],
                'ja' => '子どもが急に道路に出てくる可能性がある場所では、速度を落として注意する。',
                'en' => 'In places where a child might suddenly enter the road, reduce speed and stay alert.',
                'answer' => true,
                'explain_en' => 'Anticipate unpredictable movement near schools and residential streets.',
                'difficulty' => 1,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111116',
                'category' => 'traffic-signals',
                'exams' => ['gaimen-kirikae'],
                'ja' => '黄信号になったときは、無理に交差点へ進入してよい。',
                'en' => 'When the light turns yellow, you may rush into the intersection.',
                'answer' => false,
                'explain_en' => 'Yellow means prepare to stop if you can do so safely.',
                'difficulty' => 2,
            ],
            [
                'uuid' => '11111111-1111-4111-8111-111111111117',
                'category' => 'expressways',
                'exams' => ['honmen'],
                'ja' => '高速道路の路肩は、通常の走行車線として使ってよい。',
                'en' => 'The expressway shoulder may be used as a normal driving lane.',
                'answer' => false,
                'explain_en' => 'Shoulders are for emergencies and specific permitted uses, not routine travel.',
                'difficulty' => 2,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  Collection<string, int>  $categories
     * @param  Collection<string, int>  $examTypes
     */
    private function seedStandardQuestion(array $item, $categories, $examTypes, int $sourceId): void
    {
        $question = Question::query()->updateOrCreate(
            ['uuid' => $item['uuid']],
            [
                'type' => QuestionType::StandardTrueFalse,
                'status' => QuestionStatus::Demo,
                'difficulty' => $item['difficulty'],
                'japanese_text' => $item['ja'],
                'japanese_reading_data' => null,
                'correct_answer' => $item['answer'],
                'explanation_ja' => null,
                'question_category_id' => $categories[$item['category']] ?? null,
                'source_reference_id' => $sourceId,
                'source_page_number' => 'demo',
                'content_review_status' => ContentReviewStatus::Pending,
                'reviewed_at' => null,
                'is_demo' => true,
                'points' => 1,
            ]
        );

        QuestionTranslation::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'locale' => 'en',
            ],
            [
                'question_text' => $item['en'],
                'explanation' => $item['explain_en'],
                'nuance_note' => 'Demonstration sample wording. Not an official exam translation.',
                'review_status' => TranslationReviewStatus::Pending,
            ]
        );

        $ids = collect($item['exams'])
            ->map(fn (string $slug) => $examTypes[$slug] ?? null)
            ->filter()
            ->values()
            ->all();

        $question->examTypes()->sync($ids);
    }

    /**
     * @param  Collection<string, int>  $categories
     * @param  Collection<string, int>  $examTypes
     */
    private function seedIllustratedGroup($categories, $examTypes, int $sourceId): void
    {
        $uuid = '22222222-2222-4222-8222-222222222201';

        $question = Question::query()->updateOrCreate(
            ['uuid' => $uuid],
            [
                'type' => QuestionType::IllustratedGroup,
                'status' => QuestionStatus::Demo,
                'difficulty' => 3,
                'japanese_text' => '次の図の交差点の状況について、各文の正誤を答えなさい。（デモ問題）',
                'japanese_reading_data' => [
                    ['text' => '次の図の交差点の状況について、各文の正誤を答えなさい。（デモ問題）'],
                ],
                'correct_answer' => null,
                'explanation_ja' => '図示問題は全問正解で得点となる設定例（要公式確認）。',
                'question_category_id' => $categories['intersections'] ?? null,
                'source_reference_id' => $sourceId,
                'source_page_number' => 'demo-illustration',
                'content_review_status' => ContentReviewStatus::Pending,
                'reviewed_at' => null,
                'is_demo' => true,
                'points' => 2,
            ]
        );

        QuestionTranslation::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'locale' => 'en',
            ],
            [
                'question_text' => 'About the intersection shown in the figure, judge each statement true or false. (Demo question)',
                'explanation' => 'Illustrated groups are configured as all-or-nothing scoring examples and require official-rule verification before release.',
                'nuance_note' => 'Demo illustration only; not an official exam figure.',
                'review_status' => TranslationReviewStatus::Pending,
            ]
        );

        QuestionMedia::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'path' => 'images/demo/intersection-preview.svg',
            ],
            [
                'disk' => 'web',
                'media_type' => QuestionMediaType::Illustration,
                'alt_text' => 'Demonstration illustration of a simple intersection with a warning cue and guidance sign',
                'mime_type' => 'image/svg+xml',
                'width' => 480,
                'height' => 320,
                'sort_order' => 0,
            ]
        );

        $statements = [
            [
                'order' => 1,
                'ja' => '横断歩道上に歩行者がいるときは、車両は一時停止して進路を譲る。',
                'en' => 'If a pedestrian is on the crosswalk, vehicles must stop and yield.',
                'answer' => true,
                'explain' => 'Pedestrians on a crosswalk have priority.',
            ],
            [
                'order' => 2,
                'ja' => '見通しが悪くても、減速せずに交差点へ進入してよい。',
                'en' => 'Even with poor visibility, you may enter the intersection without slowing down.',
                'answer' => false,
                'explain' => 'Slow down and confirm safety when sight lines are limited.',
            ],
            [
                'order' => 3,
                'ja' => '右折する車両は、対向直進車の進行を妨げてはならない。',
                'en' => 'A vehicle turning right must not obstruct oncoming straight-through traffic.',
                'answer' => true,
                'explain' => 'Oncoming through traffic generally has priority over a right turn.',
            ],
        ];

        foreach ($statements as $statement) {
            $model = QuestionStatement::query()->updateOrCreate(
                [
                    'question_id' => $question->id,
                    'sort_order' => $statement['order'],
                ],
                [
                    'japanese_text' => $statement['ja'],
                    'japanese_reading_data' => null,
                    'correct_answer' => $statement['answer'],
                    'explanation_ja' => null,
                ]
            );

            QuestionStatementTranslation::query()->updateOrCreate(
                [
                    'question_statement_id' => $model->id,
                    'locale' => 'en',
                ],
                [
                    'statement_text' => $statement['en'],
                    'explanation' => $statement['explain'],
                    'nuance_note' => 'Demo statement translation.',
                    'review_status' => TranslationReviewStatus::Pending,
                ]
            );
        }

        $question->examTypes()->sync(array_filter([
            $examTypes['honmen'] ?? null,
        ]));
    }
}
