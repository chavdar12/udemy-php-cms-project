<?php

declare(strict_types=1);

function fizzBuzz(int $n): array
{
    $out = [];
    for ($i = 1; $i <= $n; $i++) {
        $out[] = match (true) {
            $i % 15 === 0 => 'FizzBuzz',
            $i % 3 === 0 => 'Fizz',
            $i % 5 === 0 => 'Buzz',
            default => (string) $i,
        };
    }
    return $out;
}

function letterGrade(int $score): string
{
    if ($score < 0 || $score > 100) {
        throw new RangeException('Score must be between 0 and 100');
    }
    if ($score >= 90) {
        return 'A';
    } elseif ($score >= 80) {
        return 'B';
    } elseif ($score >= 70) {
        return 'C';
    } elseif ($score >= 60) {
        return 'D';
    }
    return 'F';
}

function sumOfEvens(array $numbers): int
{
    $sum = 0;
    foreach ($numbers as $n) {
        if ($n % 2 !== 0) {
            continue;
        }
        $sum += $n;
    }
    return $sum;
}

/** Collatz steps to reach 1, with a while loop. */
function collatzSteps(int $n): int
{
    if ($n < 1) {
        throw new RangeException('n must be positive');
    }
    $steps = 0;
    while ($n !== 1) {
        $n = $n % 2 === 0 ? intdiv($n, 2) : 3 * $n + 1;
        $steps++;
    }
    return $steps;
}
