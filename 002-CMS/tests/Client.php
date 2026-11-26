<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../../001-PHP-Basics/check.php';

use Cms\App;
use Cms\Db;
use Cms\Response;

/** Drives the App like a browser: one session, CSRF token sent automatically, fresh SQLite database. */
final class Client
{
    public PDO $db;
    public array $session = [];
    public int $regenerated = 0;
    public App $app;

    public function __construct()
    {
        $this->db = Db::connect('sqlite::memory:');
        Db::migrate($this->db);
        $this->app = new App($this->db, $this->session, function (): void {
            $this->regenerated++;
        });
    }

    public function get(string $uri): Response
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        return $this->app->handle('GET', $uri, $query);
    }

    public function post(string $uri, array $data = [], bool $withToken = true): Response
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        if ($withToken) {
            $data['_csrf'] ??= $this->app->csrfToken();
        }
        return $this->app->handle('POST', $uri, $query, $data);
    }

    public function user(string $username, string $role = 'subscriber', string $password = 'password123'): int
    {
        $this->db->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute([$username, "$username@example.com", password_hash($password, PASSWORD_DEFAULT), $role]);
        return (int) $this->db->lastInsertId();
    }

    public function login(string $username, string $password = 'password123'): Response
    {
        return $this->post('/login', ['username' => $username, 'password' => $password]);
    }

    public function category(string $title): int
    {
        $this->db->prepare('INSERT INTO categories (title) VALUES (?)')->execute([$title]);
        return (int) $this->db->lastInsertId();
    }

    public function postRow(int $categoryId, int $userId, string $title, string $status = 'published', string $tags = ''): int
    {
        $this->db->prepare('INSERT INTO posts (category_id, user_id, title, content, tags, status) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$categoryId, $userId, $title, "Body of $title", $tags, $status]);
        return (int) $this->db->lastInsertId();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}

function redirectsTo(Response $response, string $to): bool
{
    return $response->status === 303 && ($response->headers['Location'] ?? null) === $to;
}
