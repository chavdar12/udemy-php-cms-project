<?php

declare(strict_types=1);

require __DIR__ . '/Client.php';

$c = new Client();
$ana = $c->user('ana', 'admin');
$cat = $c->category('<b>Cat</b>');
$c->postRow($cat, $ana, '<script>alert(1)</script>');

// every POST needs the session's CSRF token
check($c->post('/register', ['username' => 'eve', 'email' => 'eve@example.com', 'password' => 'password123'], false)->status === 403, 'POST without a token is rejected');
check($c->post('/login', ['username' => 'ana', 'password' => 'password123', '_csrf' => 'forged'])->status === 403, 'POST with a wrong token is rejected');
check($c->post('/login', ['username' => 'ana', 'password' => 'password123', '_csrf' => ['array']])->status === 403, 'token must be a string');
check((int) $c->value("SELECT COUNT(*) FROM users WHERE username = 'eve'") === 0, 'rejected POST changed nothing');
$before = $c->app->csrfToken();
$c->login('ana');
check($c->app->csrfToken() !== $before, 'login rotates the CSRF token');

// output is escaped everywhere
$home = $c->get('/')->body;
check(!str_contains($home, '<script>alert(1)</script>') && str_contains($home, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'post title is escaped');
check(!str_contains($home, '<b>Cat</b>') && str_contains($home, '&lt;b&gt;Cat&lt;/b&gt;'), 'category title is escaped');
$search = $c->get('/search?q=%22%3E%3Cimg+src%3Dx%3E')->body;
check(!str_contains($search, '"><img') && str_contains($search, '&quot;&gt;&lt;img'), 'search term is escaped');
$c->post('/post?id=1', ['author' => '<i>x</i>', 'email' => 'x@example.com', 'content' => '<img src=x onerror=alert(1)>']);
$c->db->exec("UPDATE comments SET status = 'approved'");
$post = $c->get('/post?id=1')->body;
check(!str_contains($post, '<img src=x') && !str_contains($post, '<i>x</i>'), 'comments are escaped');

// SQL injection goes nowhere
check($c->get("/post?id=1%20OR%201=1")->status === 404, 'non-numeric id');
check(str_contains($c->get("/search?q=' OR '1'='1")->body, 'No posts found'), 'search is parameterized');
$c->post('/logout');
check($c->login("' OR '1'='1", "' OR '1'='1")->status === 422, 'login is parameterized');

// non-string input never reaches the database as a string
check($c->post('/contact', ['name' => ['x'], 'email' => ['y'], 'subject' => 's', 'body' => 'b'])->status === 422, 'array fields are rejected');

// passwords and session
check(password_get_info((string) $c->value('SELECT password_hash FROM users'))['algo'] !== null, 'passwords are hashed');
$c->login('ana');
$c->db->exec('DELETE FROM users');
check(redirectsTo($c->get('/admin'), '/login'), 'a deleted user loses access at once');
