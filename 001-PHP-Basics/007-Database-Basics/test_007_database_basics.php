<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/UserRepository.php';

$users = new UserRepository(new PDO('sqlite::memory:'));
$id = $users->create('ana', 'secret-pass');
$users->create('bob', 'another-pass');
check($users->all() === [['id' => 1, 'username' => 'ana'], ['id' => 2, 'username' => 'bob']], 'create and list');

check($users->login('ana', 'secret-pass'), 'login with the right password');
check(!$users->login('ana', 'wrong-pass'), 'login with a wrong password fails');
check(!$users->login("' OR '1'='1", "' OR '1'='1"), 'SQL injection in the login does nothing');

check($users->rename($id, 'anna') && $users->login('anna', 'secret-pass'), 'rename');
check($users->delete(2) && !$users->delete(2), 'delete');

try {
    $users->create('x', 'short');
    check(false, 'weak input is rejected');
} catch (InvalidArgumentException) {
    check(true, 'weak input is rejected');
}
