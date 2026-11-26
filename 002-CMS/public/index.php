<?php

declare(strict_types=1);

// Front controller. Run with: php -S localhost:8000 -t public public/index.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/../src/bootstrap.php';

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);

$db = Cms\Db::connect(
    getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../var/cms.sqlite',
    getenv('DB_USER') ?: null,
    getenv('DB_PASSWORD') ?: null,
);
$response = (new Cms\App($db, $_SESSION))->handle($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_GET, $_POST);

http_response_code($response->status);
header("Content-Security-Policy: default-src 'self'");
header('X-Content-Type-Options: nosniff');
foreach ($response->headers as $name => $value) {
    header("$name: $value");
}
echo $response->body;
