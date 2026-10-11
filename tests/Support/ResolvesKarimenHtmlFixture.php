<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;

trait ResolvesKarimenHtmlFixture
{
    protected function resolveFullKarimenHtmlPath(): string
    {
        $fixture = base_path('tests/Fixtures/Imports/karimen_exam_trainer.html');
        $storage = storage_path('app/menkyo-imports/karimen_exam_trainer (1).html');

        if (is_file($fixture)) {
            return $fixture;
        }

        if (is_file($storage)) {
            File::ensureDirectoryExists(dirname($fixture));
            File::copy($storage, $fixture);

            return $fixture;
        }

        $this->markTestSkipped('Full Karimen HTML source is unavailable locally (intentionally untracked).');
    }
}
