<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog CMS</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<nav>
    <a href="/">Blog</a>
    <a href="/contact">Contact</a>
    <form action="/search" method="get" role="search">
        <input name="q" placeholder="Search posts" value="<?= e($q ?? '') ?>" maxlength="100">
    </form>
    <?php if ($user): ?>
        <a href="<?= $user['role'] === 'admin' ? '/admin' : '/admin/profile' ?>"><?= e($user['username']) ?></a>
        <form action="/logout" method="post">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button>Log out</button>
        </form>
    <?php else: ?>
        <a href="/login">Log in</a>
        <a href="/register">Register</a>
    <?php endif ?>
</nav>
<?php if ($user && $user['role'] === 'admin'): ?>
    <nav class="admin">
        <a href="/admin">Dashboard</a>
        <a href="/admin/posts">Posts</a>
        <a href="/admin/categories">Categories</a>
        <a href="/admin/comments">Comments</a>
        <a href="/admin/users">Users</a>
        <a href="/admin/messages">Messages</a>
        <a href="/admin/profile">Profile</a>
    </nav>
<?php endif ?>
<?php if ($flash): ?>
    <p class="flash" role="status"><?= e($flash) ?></p>
<?php endif ?>
<div class="page">
    <main><?= $content ?></main>
    <aside>
        <h2>Categories</h2>
        <ul>
            <?php foreach ($categories as $category): ?>
                <li><a href="/category?id=<?= e($category['id']) ?>"><?= e($category['title']) ?></a></li>
            <?php endforeach ?>
        </ul>
    </aside>
</div>
</body>
</html>
