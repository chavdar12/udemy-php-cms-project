<?php

declare(strict_types=1);

require __DIR__ . '/Client.php';

$c = new Client();
$ana = $c->user('ana');
$php = $c->category('PHP');
$css = $c->category('CSS');
for ($i = 1; $i <= 7; $i++) {
    $c->postRow($php, $ana, "Post $i", tags: $i === 3 ? 'arrays, loops' : '');
}
$draft = $c->postRow($css, $ana, 'Secret draft', 'draft');

// home page: newest published first, five per page, never drafts
$home = $c->get('/');
check($home->status === 200 && str_contains($home->body, 'Post 7') && !str_contains($home->body, 'Post 2'), 'home shows the newest page');
check(!str_contains($home->body, 'Secret draft'), 'home hides drafts');
$page2 = $c->get('/?page=2');
check(str_contains($page2->body, 'Post 2') && str_contains($page2->body, 'Post 1') && !str_contains($page2->body, 'Post 3'), 'second page');
check(str_contains($c->get('/?page=99')->body, 'Post 2'), 'a page past the end shows the last page');
check(str_contains($home->body, '>CSS</a>') && str_contains($home->body, '>PHP</a>'), 'sidebar lists categories');

// single post, view counter, drafts are 404 for visitors
$post = $c->get('/post?id=1');
check($post->status === 200 && str_contains($post->body, 'Body of Post 1'), 'post page');
$c->get('/post?id=1');
check((int) $c->value('SELECT views FROM posts WHERE id = 1') === 2, 'views are counted');
check($c->get("/post?id=$draft")->status === 404, 'draft is hidden from visitors');
check($c->get('/post?id=abc')->status === 404 && $c->get('/post?id=-1')->status === 404, 'bad ids are 404');

// category, author, search
$category = $c->get("/category?id=$css");
check($category->status === 200 && str_contains($category->body, 'No posts found'), 'category without published posts');
check($c->get('/category?id=999')->status === 404, 'unknown category is 404');
check(substr_count($c->get("/author?id=$ana")->body, '<article>') === 7, 'author page lists their published posts');
check(str_contains($c->get('/search?q=loops')->body, 'Post 3'), 'search by tag');
check(substr_count($c->get('/search?q=post')->body, '<article>') === 7, 'search by title, case-insensitive');
check(str_contains($c->get('/search?q=%25')->body, 'No posts found'), 'a % in the search is literal, not a wildcard');
check(str_contains($c->get('/search?q=')->body, 'No posts found'), 'empty search finds nothing');

// comments are moderated
$bad = $c->post('/post?id=1', ['author' => '', 'email' => 'nope', 'content' => '']);
check($bad->status === 422 && str_contains($bad->body, 'Enter a valid email address.'), 'comment validation');
$ok = $c->post('/post?id=1', ['author' => 'Bob', 'email' => 'bob@example.com', 'content' => 'Nice post']);
check(redirectsTo($ok, '/post?id=1'), 'comment is saved');
check(!str_contains($c->get('/post?id=1')->body, 'Nice post'), 'pending comment is not shown');
$c->db->exec("UPDATE comments SET status = 'approved'");
check(str_contains($c->get('/post?id=1')->body, 'Nice post'), 'approved comment is shown');
check($c->post("/post?id=$draft", ['author' => 'Bob', 'email' => 'bob@example.com', 'content' => 'x'])->status === 404, 'no comments on drafts');

// register, login, logout
$weak = $c->post('/register', ['username' => 'a!', 'email' => 'ana@example.com', 'password' => 'short']);
check($weak->status === 422 && str_contains($weak->body, 'Use at least 8 characters.') && str_contains($weak->body, 'That email is already registered.'), 'register validation');
check(redirectsTo($c->post('/register', ['username' => 'carl', 'email' => 'carl@example.com', 'password' => 'long-enough']), '/login'), 'register');
check($c->value("SELECT role FROM users WHERE username = 'carl'") === 'subscriber', 'new users are subscribers');
check(!str_contains((string) $c->value("SELECT password_hash FROM users WHERE username = 'carl'"), 'long-enough'), 'password is hashed');
check($c->post('/register', ['username' => 'carl', 'email' => 'c2@example.com', 'password' => 'long-enough'])->status === 422, 'duplicate username');

check($c->login('carl', 'wrong-password')->status === 422, 'wrong password');
check(redirectsTo($c->login('carl@example.com', 'long-enough'), '/'), 'login with email');
check($c->regenerated === 1, 'login regenerates the session id');
check(str_contains($c->get('/')->body, '>carl</a>'), 'logged-in user shows in the menu');
check(redirectsTo($c->post('/logout'), '/') && $c->session === [], 'logout clears the session');

// contact form
check($c->post('/contact', ['name' => 'Dee', 'email' => 'bad', 'subject' => '', 'body' => 'Hi'])->status === 422, 'contact validation');
check(redirectsTo($c->post('/contact', ['name' => 'Dee', 'email' => 'dee@example.com', 'subject' => 'Hello', 'body' => 'Hi']), '/contact'), 'contact');
check((int) $c->value('SELECT COUNT(*) FROM messages') === 1, 'message is stored');
check(str_contains($c->get('/contact')->body, 'Thanks, your message was sent.'), 'flash message is shown once');
check(!str_contains($c->get('/contact')->body, 'Thanks, your message was sent.'), 'flash message is gone after one view');

check($c->get('/nowhere')->status === 404, 'unknown route is 404');
