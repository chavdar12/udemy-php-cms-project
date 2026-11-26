<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/functions.php';

check(total() === 0.0 && total(1.5, 2.5, 6) === 10.0, 'total (variadic)');
check(withTax(100) === 120.0 && withTax(100, 0.09) === 109.0, 'withTax (default parameter)');
check(factorial(0) === 1 && factorial(5) === 120, 'factorial (recursion)');
$counter = makeCounter(10);
$counter();
check($counter() === 12, 'makeCounter (closure keeps state)');
check(makeCounter()() === 1, 'a new counter starts again');
check(longWords(['php', 'array', 'café', 'go'], 4) === ['array', 'café'], 'longWords (arrow function, multibyte)');
