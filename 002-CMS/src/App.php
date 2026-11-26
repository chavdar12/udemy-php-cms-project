<?php

declare(strict_types=1);

namespace Cms;

use Closure;
use PDO;

/**
 * The whole CMS: one front controller with a route table. It takes the request as plain arrays and returns a
 * Response, so tests drive it without a web server.
 */
final class App
{
    public const PER_PAGE = 5;
    private const STATUSES = ['draft', 'published'];
    private const ROLES = ['subscriber', 'admin'];

    private array $session;
    private Closure $regenerate;

    public function __construct(private PDO $db, array &$session, ?Closure $regenerate = null)
    {
        $this->session = &$session;
        $this->regenerate = $regenerate
            ?? fn() => session_status() === PHP_SESSION_ACTIVE && session_regenerate_id(true);
    }

    public function handle(string $method, string $uri, array $query = [], array $post = []): Response
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        if ($method === 'POST' && !hash_equals($this->csrfToken(), is_string($post['_csrf'] ?? null) ? $post['_csrf'] : '')) {
            return $this->page('error', ['message' => 'Your session expired. Reload the page and try again.'], 403);
        }
        if (str_starts_with($path, '/admin')) {
            $user = $this->user();
            if ($user === null) {
                return Response::redirect('/login');
            }
            if ($user['role'] !== 'admin' && $path !== '/admin/profile') {
                return $this->page('error', ['message' => 'Only admins can open this page.'], 403);
            }
        }

        return match ("$method $path") {
            'GET /' => $this->home($query),
            'GET /post' => $this->showPost($query),
            'POST /post' => $this->addComment($query, $post),
            'GET /category' => $this->category($query),
            'GET /author' => $this->author($query),
            'GET /search' => $this->search($query),
            'GET /register' => $this->page('register'),
            'POST /register' => $this->register($post),
            'GET /login' => $this->page('login'),
            'POST /login' => $this->login($post),
            'POST /logout' => $this->logout(),
            'GET /contact' => $this->page('contact'),
            'POST /contact' => $this->contact($post),
            'GET /admin' => $this->dashboard(),
            'GET /admin/posts' => $this->adminPosts(),
            'GET /admin/posts/new' => $this->postForm(),
            'POST /admin/posts/new' => $this->savePost(null, $post),
            'GET /admin/posts/edit' => $this->id($query) ? $this->postForm($this->id($query)) : $this->notFound(),
            'POST /admin/posts/edit' => $this->id($query) ? $this->savePost($this->id($query), $post) : $this->notFound(),
            'POST /admin/posts/delete' => $this->deleteRow('posts', $post, '/admin/posts', 'Post deleted.'),
            'GET /admin/categories' => $this->adminCategories(),
            'POST /admin/categories' => $this->saveCategory($post),
            'POST /admin/categories/delete' => $this->deleteCategory($post),
            'GET /admin/comments' => $this->adminComments(),
            'POST /admin/comments/status' => $this->commentStatus($post),
            'POST /admin/comments/delete' => $this->deleteRow('comments', $post, '/admin/comments', 'Comment deleted.'),
            'GET /admin/users' => $this->adminUsers(),
            'POST /admin/users' => $this->createUser($post),
            'POST /admin/users/role' => $this->userRole($post),
            'POST /admin/users/delete' => $this->deleteUser($post),
            'GET /admin/messages' => $this->page('admin/messages', ['messages' => $this->all('SELECT * FROM messages ORDER BY id DESC')]),
            'GET /admin/profile' => $this->page('admin/profile', ['profile' => $this->user()]),
            'POST /admin/profile' => $this->updateProfile($post),
            default => $this->notFound(),
        };
    }

    public function csrfToken(): string
    {
        return $this->session['csrf'] ??= bin2hex(random_bytes(32));
    }

    public function user(): ?array
    {
        $id = $this->session['user_id'] ?? null;
        return $id === null ? null : $this->one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    // ---- front ----

    private function home(array $query): Response
    {
        $total = (int) $this->value("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, $this->id($query, 'page') ?? 1);
        $posts = $this->all(
            $this->postSql("WHERE p.status = 'published'") . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . ($page - 1) * self::PER_PAGE,
        );
        return $this->page('posts', ['heading' => 'Latest posts', 'posts' => $posts, 'page' => $page, 'pages' => $pages]);
    }

    private function showPost(array $query, array $form = [], array $errors = [], int $status = 200): Response
    {
        $post = $this->visiblePost($this->id($query));
        if ($post === null) {
            return $this->notFound();
        }
        if ($status === 200 && $errors === []) {
            $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$post['id']]);
        }
        $comments = $this->all("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY id", [$post['id']]);
        return $this->page('post', compact('post', 'comments', 'form', 'errors'), $status);
    }

    private function addComment(array $query, array $post): Response
    {
        $target = $this->visiblePost($this->id($query));
        if ($target === null || $target['status'] !== 'published') {
            return $this->notFound();
        }
        $form = $this->fields($post, ['author', 'email', 'content']);
        $errors = $this->required($form, ['author' => 100, 'email' => 255, 'content' => 2000]);
        if (!isset($errors['email']) && filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($errors !== []) {
            return $this->showPost($query, $form, $errors, 422);
        }
        $this->db->prepare('INSERT INTO comments (post_id, author, email, content) VALUES (?, ?, ?, ?)')
            ->execute([$target['id'], $form['author'], $form['email'], $form['content']]);
        return $this->flash('Thanks! Your comment will appear once it is approved.', "/post?id={$target['id']}");
    }

    private function category(array $query): Response
    {
        $category = $this->one('SELECT * FROM categories WHERE id = ?', [$this->id($query)]);
        if ($category === null) {
            return $this->notFound();
        }
        $posts = $this->all($this->postSql("WHERE p.status = 'published' AND p.category_id = ?"), [$category['id']]);
        return $this->page('posts', ['heading' => "Category: {$category['title']}", 'posts' => $posts]);
    }

    private function author(array $query): Response
    {
        $author = $this->one('SELECT id, username FROM users WHERE id = ?', [$this->id($query)]);
        if ($author === null) {
            return $this->notFound();
        }
        $posts = $this->all($this->postSql("WHERE p.status = 'published' AND p.user_id = ?"), [$author['id']]);
        return $this->page('posts', ['heading' => "Posts by {$author['username']}", 'posts' => $posts]);
    }

    private function search(array $query): Response
    {
        $term = mb_substr(trim(is_string($query['q'] ?? null) ? $query['q'] : ''), 0, 100);
        $posts = [];
        if ($term !== '') {
            // match the term literally: % and _ typed by the user are not wildcards
            $like = '%' . strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $posts = $this->all(
                $this->postSql("WHERE p.status = 'published' AND (p.title LIKE ? ESCAPE '!' OR p.tags LIKE ? ESCAPE '!')"),
                [$like, $like],
            );
        }
        return $this->page('posts', ['heading' => "Search: $term", 'posts' => $posts, 'q' => $term]);
    }

    private function register(array $post): Response
    {
        $form = $this->fields($post, ['username', 'email', 'password']);
        $errors = $this->userErrors($form);
        if ($errors !== []) {
            return $this->page('register', compact('form', 'errors'), 422);
        }
        $this->insertUser($form, 'subscriber');
        return $this->flash('Account created. You can log in now.', '/login');
    }

    private function login(array $post): Response
    {
        $form = $this->fields($post, ['username', 'password']);
        $user = $this->one('SELECT * FROM users WHERE username = ? OR email = ?', [$form['username'], $form['username']]);
        if ($user === null || !password_verify($form['password'], $user['password_hash'])) {
            return $this->page('login', ['form' => $form, 'errors' => ['login' => 'Wrong username or password.']], 422);
        }
        ($this->regenerate)();
        $this->session['user_id'] = $user['id'];
        unset($this->session['csrf']);
        return Response::redirect($user['role'] === 'admin' ? '/admin' : '/');
    }

    private function logout(): Response
    {
        $this->session = [];
        ($this->regenerate)();
        return Response::redirect('/');
    }

    private function contact(array $post): Response
    {
        $form = $this->fields($post, ['name', 'email', 'subject', 'body']);
        $errors = $this->required($form, ['name' => 100, 'email' => 255, 'subject' => 255, 'body' => 5000]);
        if (!isset($errors['email']) && filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($errors !== []) {
            return $this->page('contact', compact('form', 'errors'), 422);
        }
        $this->db->prepare('INSERT INTO messages (name, email, subject, body) VALUES (?, ?, ?, ?)')
            ->execute([$form['name'], $form['email'], $form['subject'], $form['body']]);
        return $this->flash('Thanks, your message was sent.', '/contact');
    }

    // ---- admin ----

    private function dashboard(): Response
    {
        $counts = [];
        foreach ([
            'Posts' => 'SELECT COUNT(*) FROM posts',
            'Published posts' => "SELECT COUNT(*) FROM posts WHERE status = 'published'",
            'Drafts' => "SELECT COUNT(*) FROM posts WHERE status = 'draft'",
            'Comments' => 'SELECT COUNT(*) FROM comments',
            'Pending comments' => "SELECT COUNT(*) FROM comments WHERE status = 'pending'",
            'Users' => 'SELECT COUNT(*) FROM users',
            'Categories' => 'SELECT COUNT(*) FROM categories',
            'Messages' => 'SELECT COUNT(*) FROM messages',
        ] as $label => $sql) {
            $counts[$label] = (int) $this->value($sql);
        }
        return $this->page('admin/dashboard', ['counts' => $counts]);
    }

    private function adminPosts(): Response
    {
        return $this->page('admin/posts', ['posts' => $this->all($this->postSql(''))]);
    }

    private function postForm(?int $id = null, array $form = [], array $errors = [], int $status = 200): Response
    {
        if ($id !== null && $form === []) {
            $form = $this->one('SELECT * FROM posts WHERE id = ?', [$id]) ?? [];
            if ($form === []) {
                return $this->notFound();
            }
        }
        $categories = $this->all('SELECT * FROM categories ORDER BY title');
        return $this->page('admin/post-form', compact('id', 'form', 'errors', 'categories'), $status);
    }

    private function savePost(?int $id, array $post): Response
    {
        if ($id !== null && $this->one('SELECT id FROM posts WHERE id = ?', [$id]) === null) {
            return $this->notFound();
        }
        $form = $this->fields($post, ['title', 'category_id', 'content', 'tags', 'status']);
        $errors = $this->required($form, ['title' => 255, 'content' => 100000]);
        if (mb_strlen($form['tags']) > 255) {
            $errors['tags'] = 'Tags are too long.';
        }
        if (!in_array($form['status'], self::STATUSES, true)) {
            $errors['status'] = 'Pick draft or published.';
        }
        if ($this->one('SELECT id FROM categories WHERE id = ?', [$this->id($form, 'category_id')]) === null) {
            $errors['category_id'] = 'Pick a category.';
        }
        if ($errors !== []) {
            return $this->postForm($id, $form, $errors, 422);
        }
        $values = [$form['title'], (int) $form['category_id'], $form['content'], $form['tags'], $form['status']];
        if ($id === null) {
            $this->db->prepare('INSERT INTO posts (title, category_id, content, tags, status, user_id) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([...$values, $this->session['user_id']]);
        } else {
            $this->db->prepare('UPDATE posts SET title = ?, category_id = ?, content = ?, tags = ?, status = ? WHERE id = ?')
                ->execute([...$values, $id]);
        }
        return $this->flash($id === null ? 'Post created.' : 'Post updated.', '/admin/posts');
    }

    private function adminCategories(array $errors = [], int $status = 200): Response
    {
        $categories = $this->all(
            'SELECT c.*, COUNT(p.id) AS posts FROM categories c LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id, c.title ORDER BY c.title',
        );
        return $this->page('admin/categories', compact('categories', 'errors'), $status);
    }

    /** Adds a category, or renames one when the form has an id. */
    private function saveCategory(array $post): Response
    {
        $form = $this->fields($post, ['title']);
        $id = $this->id($post);
        $errors = $this->required($form, ['title' => 100]);
        if ($errors === [] && $this->one('SELECT id FROM categories WHERE title = ? AND id <> ?', [$form['title'], $id ?? 0])) {
            $errors['title'] = 'That category already exists.';
        }
        if ($errors !== []) {
            return $this->adminCategories($errors, 422);
        }
        if ($id === null) {
            $this->db->prepare('INSERT INTO categories (title) VALUES (?)')->execute([$form['title']]);
        } else {
            $this->db->prepare('UPDATE categories SET title = ? WHERE id = ?')->execute([$form['title'], $id]);
        }
        return $this->flash('Category saved.', '/admin/categories');
    }

    private function deleteCategory(array $post): Response
    {
        if ((int) $this->value('SELECT COUNT(*) FROM posts WHERE category_id = ?', [$this->id($post) ?? 0]) > 0) {
            return $this->adminCategories(['title' => 'Move or delete its posts before deleting a category.'], 409);
        }
        return $this->deleteRow('categories', $post, '/admin/categories', 'Category deleted.');
    }

    private function adminComments(): Response
    {
        $comments = $this->all('SELECT c.*, p.title AS post_title FROM comments c JOIN posts p ON p.id = c.post_id ORDER BY c.id DESC');
        return $this->page('admin/comments', ['comments' => $comments]);
    }

    private function commentStatus(array $post): Response
    {
        $status = $post['status'] ?? null;
        if (!in_array($status, ['pending', 'approved'], true)) {
            return $this->page('error', ['message' => 'Unknown comment status.'], 422);
        }
        $this->db->prepare('UPDATE comments SET status = ? WHERE id = ?')->execute([$status, $this->id($post) ?? 0]);
        return $this->flash($status === 'approved' ? 'Comment approved.' : 'Comment hidden.', '/admin/comments');
    }

    private function adminUsers(array $form = [], array $errors = [], int $status = 200): Response
    {
        $users = $this->all('SELECT id, username, email, role, created_at FROM users ORDER BY id');
        return $this->page('admin/users', compact('users', 'form', 'errors'), $status);
    }

    private function createUser(array $post): Response
    {
        $form = $this->fields($post, ['username', 'email', 'password', 'role']);
        $errors = $this->userErrors($form);
        if (!in_array($form['role'], self::ROLES, true)) {
            $errors['role'] = 'Pick a role.';
        }
        if ($errors !== []) {
            return $this->adminUsers($form, $errors, 422);
        }
        $this->insertUser($form, $form['role']);
        return $this->flash('User created.', '/admin/users');
    }

    private function userRole(array $post): Response
    {
        $role = $post['role'] ?? null;
        $id = $this->id($post);
        if (!in_array($role, self::ROLES, true) || $id === null) {
            return $this->page('error', ['message' => 'Unknown role.'], 422);
        }
        if ($id === $this->session['user_id']) {
            return $this->page('error', ['message' => 'You cannot change your own role.'], 409);
        }
        $this->db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        return $this->flash('Role updated.', '/admin/users');
    }

    private function deleteUser(array $post): Response
    {
        if ($this->id($post) === $this->session['user_id']) {
            return $this->page('error', ['message' => 'You cannot delete your own account here.'], 409);
        }
        return $this->deleteRow('users', $post, '/admin/users', 'User deleted.');
    }

    private function updateProfile(array $post): Response
    {
        $user = $this->user();
        $form = $this->fields($post, ['first_name', 'last_name', 'email', 'password']);
        $errors = [];
        foreach (['first_name', 'last_name'] as $name) {
            if (mb_strlen($form[$name]) > 100) {
                $errors[$name] = 'Too long.';
            }
        }
        if (filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->one('SELECT id FROM users WHERE email = ? AND id <> ?', [$form['email'], $user['id']])) {
            $errors['email'] = 'That email is already used.';
        }
        if ($form['password'] !== '' && strlen($form['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters, or leave it empty to keep the current password.';
        }
        if ($errors !== []) {
            return $this->page('admin/profile', ['profile' => [...$user, ...$form], 'errors' => $errors], 422);
        }
        $this->db->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?')
            ->execute([$form['first_name'], $form['last_name'], $form['email'], $user['id']]);
        if ($form['password'] !== '') {
            $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($form['password'], PASSWORD_DEFAULT), $user['id']]);
        }
        return $this->flash('Profile saved.', '/admin/profile');
    }

    // ---- helpers ----

    /** A post the current visitor may see: published ones for everybody, drafts for admins too. */
    private function visiblePost(?int $id): ?array
    {
        $post = $this->one($this->postSql('WHERE p.id = ?'), [$id ?? 0]);
        if ($post === null || ($post['status'] !== 'published' && ($this->user()['role'] ?? null) !== 'admin')) {
            return null;
        }
        return $post;
    }

    private function postSql(string $where): string
    {
        return "SELECT p.*, c.title AS category, u.username AS author
            FROM posts p JOIN categories c ON c.id = p.category_id JOIN users u ON u.id = p.user_id
            $where ORDER BY p.id DESC";
    }

    private function userErrors(array $form): array
    {
        $errors = [];
        if (!preg_match('/^\w{3,50}$/', $form['username'])) {
            $errors['username'] = 'Use 3-50 letters, digits or underscores.';
        } elseif ($this->one('SELECT id FROM users WHERE username = ?', [$form['username']])) {
            $errors['username'] = 'That username is taken.';
        }
        if (filter_var($form['email'], FILTER_VALIDATE_EMAIL) === false || strlen($form['email']) > 255) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->one('SELECT id FROM users WHERE email = ?', [$form['email']])) {
            $errors['email'] = 'That email is already registered.';
        }
        if (strlen($form['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        return $errors;
    }

    private function insertUser(array $form, string $role): void
    {
        $this->db->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute([$form['username'], $form['email'], password_hash($form['password'], PASSWORD_DEFAULT), $role]);
    }

    private function deleteRow(string $table, array $post, string $back, string $message): Response
    {
        // $table is always one of our literal table names, never user input
        $this->db->prepare("DELETE FROM $table WHERE id = ?")->execute([$this->id($post) ?? 0]);
        return $this->flash($message, $back);
    }

    /** Trimmed string values for the given keys; anything missing or not a string becomes ''. */
    private function fields(array $input, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = is_string($input[$key] ?? null) ? trim($input[$key]) : '';
        }
        return $out;
    }

    /** @param array<string, int> $limits field => max length */
    private function required(array $form, array $limits): array
    {
        $errors = [];
        foreach ($limits as $field => $max) {
            if ($form[$field] === '' || mb_strlen($form[$field]) > $max) {
                $errors[$field] = "Required, up to $max characters.";
            }
        }
        return $errors;
    }

    private function id(array $input, string $key = 'id'): ?int
    {
        $id = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private function flash(string $message, string $to): Response
    {
        $this->session['flash'] = $message;
        return Response::redirect($to);
    }

    private function notFound(): Response
    {
        return $this->page('error', ['message' => 'Page not found.'], 404);
    }

    private function page(string $template, array $data = [], int $status = 200): Response
    {
        $shared = [
            'user' => $this->user(),
            'csrf' => $this->csrfToken(),
            'flash' => $this->session['flash'] ?? null,
            'categories' => $data['categories'] ?? $this->all('SELECT * FROM categories ORDER BY title'),
        ];
        unset($this->session['flash']);
        return new Response($status, View::render($template, [...$shared, ...$data]));
    }

    private function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    private function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function value(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
