<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/control_flow.php';

check(fizzBuzz(15) === ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'], 'fizzBuzz');
check(array_map('letterGrade', [100, 90, 89, 75, 60, 59, 0]) === ['A', 'A', 'B', 'C', 'D', 'F', 'F'], 'letterGrade');
try {
    letterGrade(101);
    check(false, 'letterGrade rejects 101');
} catch (RangeException) {
    check(true, 'letterGrade rejects 101');
}
check(sumOfEvens([1, 2, 3, 4, -6]) === 0, 'sumOfEvens with a negative even');
check(sumOfEvens([1, 2, 3, 4, 10]) === 16, 'sumOfEvens');
check(collatzSteps(1) === 0 && collatzSteps(6) === 8, 'collatzSteps');
