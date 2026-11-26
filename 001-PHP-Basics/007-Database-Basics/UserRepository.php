<?php

declare(strict_types=1);

/** The course's login-app CRUD, with prepared statements and hashed passwords. */
final class UserRepository
{
    public function __construct(private PDO $db)
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username VARCHAR(50) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL)');
    }

    public function create(string $username, string $password): int
    {
        if (!preg_match('/^\w{3,50}$/', $username) || strlen($password) < 8) {
            throw new InvalidArgumentException('Username must be 3-50 letters, digits or _ and the password at least 8 characters');
        }
        $this->db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, username FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rename(int $id, string $username): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $id]);
        return $stmt->rowCount() === 1;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }

    public function login(string $username, string $password): bool
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $hash = $stmt->fetchColumn();
        return $hash !== false && password_verify($password, $hash);
    }
}
