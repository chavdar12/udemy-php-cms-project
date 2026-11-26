<?php

declare(strict_types=1);

// Creates the tables and, on an empty database, an admin account plus a first post.
// Usage: ADMIN_PASSWORD=... php bin/setup.php  (DB_DSN / DB_USER / DB_PASSWORD pick the database)
require __DIR__ . '/../src/bootstrap.php';

$db = Cms\Db::connect(
    getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../var/cms.sqlite',
    getenv('DB_USER') ?: null,
    getenv('DB_PASSWORD') ?: null,
);
Cms\Db::migrate($db);

if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    $password = getenv('ADMIN_PASSWORD') ?: bin2hex(random_bytes(8));
    if (strlen($password) < 8) {
        fwrite(STDERR, "ADMIN_PASSWORD must have at least 8 characters\n");
        exit(1);
    }
    $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES ('admin', 'admin@example.com', ?, 'admin')")
        ->execute([password_hash($password, PASSWORD_DEFAULT)]);
    $db->exec("INSERT INTO categories (title) VALUES ('General')");
    $db->exec("INSERT INTO posts (category_id, user_id, title, content, status) VALUES (1, 1, 'Hello, world', 'The first post. Edit or delete it from the admin panel.', 'published')");
    echo getenv('ADMIN_PASSWORD') ? "Created user admin\n" : "Created user admin with password $password\n";
}
echo "Database ready\n";
