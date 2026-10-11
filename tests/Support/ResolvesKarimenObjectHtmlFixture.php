<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;

trait ResolvesKarimenObjectHtmlFixture
{
    protected function resolveFullKarimenObjectHtmlPath(): string
    {
        $fixture = base_path('tests/Fixtures/Imports/karimen_exam_trainer_object.html');
        $storage = storage_path('app/menkyo-imports/karimen_exam_trainer.html');

        if (is_file($fixture)) {
            return $fixture;
        }

        if (is_file($storage)) {
            File::ensureDirectoryExists(dirname($fixture));
            File::copy($storage, $fixture);

            return $fixture;
        }

        $this->markTestSkipped('Full object-literal Karimen HTML source is unavailable locally (intentionally untracked).');
    }
}
