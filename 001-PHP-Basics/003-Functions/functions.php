<?php

declare(strict_types=1);

function total(float ...$prices): float
{
    return array_sum($prices);
}

function withTax(float $amount, float $rate = 0.2): float
{
    return round($amount * (1 + $rate), 2);
}

function factorial(int $n): int
{
    if ($n < 0) {
        throw new InvalidArgumentException('n must not be negative');
    }
    return $n <= 1 ? 1 : $n * factorial($n - 1);
}

/** Returns a closure that counts its own calls. */
function makeCounter(int $start = 0): Closure
{
    return function () use (&$start): int {
        return ++$start;
    };
}

function longWords(array $words, int $minLength): array
{
    return array_values(array_filter($words, fn(string $w) => mb_strlen($w) >= $minLength));
}
