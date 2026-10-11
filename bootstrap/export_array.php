<?php

declare(strict_types=1);

function exportArray(mixed $value, int $indent = 0): string
{
    $pad = str_repeat('    ', $indent);
    $padIn = str_repeat('    ', $indent + 1);

    if (! is_array($value)) {
        return var_export($value, true);
    }

    if ($value === []) {
        return '[]';
    }

    if (array_is_list($value) && ! is_array($value[0] ?? null)) {
        return '['.implode(', ', array_map(static fn ($v) => var_export($v, true), $value)).']';
    }

    $lines = ["[\n"];
    foreach ($value as $k => $v) {
        $key = is_int($k) ? (string) $k : var_export($k, true);
        $lines[] = $padIn.$key.' => '.exportArray($v, $indent + 1).",\n";
    }
    $lines[] = $pad.']';

    return implode('', $lines);
}
