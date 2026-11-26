<?php

declare(strict_types=1);

/** @return array{min: int|float, max: int|float, average: float} */
function stats(array $numbers): array
{
    if ($numbers === []) {
        throw new InvalidArgumentException('Need at least one number');
    }
    return ['min' => min($numbers), 'max' => max($numbers), 'average' => (float) array_sum($numbers) / count($numbers)];
}

function greeting(string $name, int $age): string
{
    return "Hello, $name! Next year you will be " . ($age + 1) . '.';
}

/** Counts how often each word appears, case-insensitively, most frequent first. */
function wordCounts(string $text): array
{
    $words = preg_split('/[^\p{L}\p{N}\']+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
    $counts = array_count_values($words);
    arsort($counts);
    return $counts;
}

function swapKeysAndValues(array $map): array
{
    return array_flip($map);
}
