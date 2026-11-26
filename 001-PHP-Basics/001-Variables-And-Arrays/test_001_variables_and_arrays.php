<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/arrays.php';

check(stats([4, 1, 7]) === ['min' => 1, 'max' => 7, 'average' => 4.0], 'stats');
try {
    stats([]);
    check(false, 'stats rejects an empty array');
} catch (InvalidArgumentException) {
    check(true, 'stats rejects an empty array');
}
check(greeting('Ana', 29) === 'Hello, Ana! Next year you will be 30.', 'greeting');
check(wordCounts('The cat and the hat. THE end') === ['the' => 3, 'cat' => 1, 'and' => 1, 'hat' => 1, 'end' => 1], 'wordCounts');
check(swapKeysAndValues(['a' => 'x', 'b' => 'y']) === ['x' => 'a', 'y' => 'b'], 'swapKeysAndValues');
