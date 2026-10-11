<?php

namespace App\Contracts;

use App\Services\Imports\KarimenHtmlParseResult;

interface HtmlQuestionBankParser
{
    public function parseFile(string $absolutePath, ?string $fileChecksum = null): KarimenHtmlParseResult;

    public function parse(string $html, string $fileChecksum = ''): KarimenHtmlParseResult;
}
