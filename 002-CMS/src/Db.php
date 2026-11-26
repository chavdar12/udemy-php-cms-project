<?php

declare(strict_types=1);

namespace Cms;

use PDO;

final class Db
{
    public static function connect(string $dsn, ?string $user = null, ?string $password = null): PDO
    {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (self::isSqlite($pdo)) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        return $pdo;
    }

    public static function migrate(PDO $pdo): void
    {
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/../schema.sql'));
        if (self::isSqlite($pdo)) {
            // SQLite auto-increments an INTEGER PRIMARY KEY by itself and has no AUTO_INCREMENT keyword
            $sql = str_replace(' AUTO_INCREMENT', '', $sql);
        }
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }
    }

    private static function isSqlite(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }
}
