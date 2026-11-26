<?php

declare(strict_types=1);

// Run with: php -S localhost:8000 -t 004-Forms
require __DIR__ . '/form.php';

$result = $_SERVER['REQUEST_METHOD'] === 'POST' ? validateSignup($_POST) : ['data' => [], 'errors' => []];
$done = $_SERVER['REQUEST_METHOD'] === 'POST' && $result['errors'] === [];
?>
<!doctype html>
<title>Sign up</title>
<?php if ($done): ?>
    <p>Thanks, <?= e($result['data']['name']) ?>!</p>
<?php else: ?>
    <?= renderSignupForm($result['data'], $result['errors']) ?>
<?php endif ?>
