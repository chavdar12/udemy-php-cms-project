<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/oop.php';

$throws = function (callable $f): bool {
    try {
        $f();
        return false;
    } catch (InvalidArgumentException) {
        return true;
    }
};

$shapes = [new Rectangle(2, 3), new Circle(1)];
check(array_map(fn(Shape $s) => round($s->area(), 2), $shapes) === [6.0, 3.14], 'polymorphic area');
check($shapes[0]->describe() === 'Rectangle with area 6.00', 'abstract parent uses the child area');
check($throws(fn() => new Circle(0)), 'circle validates its radius');

$account = new BankAccount('Ana');
new BankAccount('Bob');
$account->deposit(50);
$account->withdraw(20);
check($account->balance() === 30.0, 'deposit and withdraw');
check($throws(fn() => $account->withdraw(100)), 'cannot overdraw');
check($throws(fn() => $account->deposit(-1)), 'cannot deposit a negative amount');
check(BankAccount::opened() === 2, 'static counter');
check($throws(fn() => (function () use ($account) {
    try {
        $account->owner = 'Eve';
    } catch (Error) {
        throw new InvalidArgumentException();
    }
})()), 'readonly owner cannot change');
