<?php

declare(strict_types=1);

require __DIR__ . '/Client.php';

$c = new Client();
$adminId = $c->user('root', 'admin');
$subscriber = $c->user('sub');
$php = $c->category('PHP');

// access control
check(redirectsTo($c->get('/admin'), '/login'), 'admin needs a login');
$c->login('sub');
check($c->get('/admin')->status === 403 && $c->get('/admin/users')->status === 403, 'subscribers cannot open the admin');
check($c->post('/admin/categories', ['title' => 'Hacked'])->status === 403, 'subscribers cannot post to the admin');
check($c->get('/admin/profile')->status === 200, 'subscribers can open their profile');
$c->post('/logout');
check(redirectsTo($c->login('root'), '/admin'), 'admins land on the dashboard');

// posts
$bad = $c->post('/admin/posts/new', ['title' => '', 'category_id' => '999', 'content' => '', 'status' => 'live']);
check($bad->status === 422 && substr_count($bad->body, 'class="error"') === 4, 'post validation');
check(redirectsTo($c->post('/admin/posts/new', ['title' => 'First', 'category_id' => (string) $php, 'content' => 'Hello', 'status' => 'draft']), '/admin/posts'), 'create post');
$postId = (int) $c->value('SELECT id FROM posts');
check((int) $c->value('SELECT user_id FROM posts') === $adminId, 'post belongs to its author');
check($c->get("/post?id=$postId")->status === 200, 'admins can preview drafts');
check(str_contains($c->get("/admin/posts/edit?id=$postId")->body, 'value="First"'), 'edit form is filled');
check($c->get('/admin/posts/edit?id=999')->status === 404 && $c->get('/admin/posts/edit')->status === 404, 'editing a missing post is 404');
$c->post("/admin/posts/edit?id=$postId", ['title' => 'First!', 'category_id' => (string) $php, 'content' => 'Hello', 'tags' => 'intro', 'status' => 'published']);
check($c->value('SELECT title || status || tags FROM posts') === 'First!publishedintro', 'update post');
check($c->post('/admin/posts/edit?id=999', ['title' => 'x', 'category_id' => (string) $php, 'content' => 'x', 'status' => 'draft'])->status === 404, 'updating a missing post is 404');

// categories
check($c->post('/admin/categories', ['title' => 'PHP'])->status === 422, 'duplicate category');
$c->post('/admin/categories', ['title' => 'CSS']);
$css = (int) $c->value("SELECT id FROM categories WHERE title = 'CSS'");
$c->post('/admin/categories', ['id' => (string) $css, 'title' => 'Styles']);
check($c->value("SELECT title FROM categories WHERE id = $css") === 'Styles', 'rename category');
check($c->post('/admin/categories/delete', ['id' => (string) $php])->status === 409, 'a category with posts cannot be deleted');
$c->post('/admin/categories/delete', ['id' => (string) $css]);
check((int) $c->value('SELECT COUNT(*) FROM categories') === 1, 'delete empty category');

// comments
$c->post("/post?id=$postId", ['author' => 'Bob', 'email' => 'bob@example.com', 'content' => 'Hi']);
$commentId = (int) $c->value('SELECT id FROM comments');
check(str_contains($c->get('/admin/comments')->body, 'Approve'), 'pending comment listed');
$c->post('/admin/comments/status', ['id' => (string) $commentId, 'status' => 'approved']);
check($c->value('SELECT status FROM comments') === 'approved', 'approve comment');
check($c->post('/admin/comments/status', ['id' => (string) $commentId, 'status' => 'spam'])->status === 422, 'unknown comment status');
$c->post('/admin/comments/delete', ['id' => (string) $commentId]);
check((int) $c->value('SELECT COUNT(*) FROM comments') === 0, 'delete comment');

// users and roles
check($c->post('/admin/users', ['username' => 'x', 'email' => 'x', 'password' => '1', 'role' => 'owner'])->status === 422, 'user validation');
$c->post('/admin/users', ['username' => 'editor', 'email' => 'editor@example.com', 'password' => 'password123', 'role' => 'admin']);
check($c->value("SELECT role FROM users WHERE username = 'editor'") === 'admin', 'create admin user');
$c->post('/admin/users/role', ['id' => (string) $subscriber, 'role' => 'admin']);
check($c->value("SELECT role FROM users WHERE id = $subscriber") === 'admin', 'change role');
check($c->post('/admin/users/role', ['id' => (string) $adminId, 'role' => 'subscriber'])->status === 409, 'cannot demote yourself');
check($c->post('/admin/users/delete', ['id' => (string) $adminId])->status === 409, 'cannot delete yourself');
$c->post('/admin/users/delete', ['id' => (string) $subscriber]);
check((int) $c->value("SELECT COUNT(*) FROM users WHERE id = $subscriber") === 0, 'delete user');

// dashboard and messages
$c->post('/contact', ['name' => 'Dee', 'email' => 'dee@example.com', 'subject' => 'Hello there', 'body' => 'Hi']);
$dash = $c->get('/admin')->body;
check(str_contains($dash, '<dt>Posts</dt><dd>1</dd>') && str_contains($dash, '<dt>Users</dt><dd>2</dd>') && str_contains($dash, '<dt>Messages</dt><dd>1</dd>'), 'dashboard counts');
check(str_contains($c->get('/admin/messages')->body, 'Hello there'), 'messages page');

// profile
check($c->post('/admin/profile', ['first_name' => 'Ro', 'last_name' => 'Ot', 'email' => 'editor@example.com', 'password' => ''])->status === 422, 'profile email must be unique');
check($c->post('/admin/profile', ['first_name' => 'Ro', 'last_name' => 'Ot', 'email' => 'root@example.com', 'password' => 'short'])->status === 422, 'profile password length');
$c->post('/admin/profile', ['first_name' => 'Ro', 'last_name' => 'Ot', 'email' => 'new@example.com', 'password' => 'new-password']);
$c->post('/logout');
check($c->login('root')->status === 422 && redirectsTo($c->login('new@example.com', 'new-password'), '/admin'), 'profile changes email and password');
