<article>
    <h1><?= e($post['title']) ?></h1>
    <p class="meta">
        by <a href="/author?id=<?= e($post['user_id']) ?>"><?= e($post['author']) ?></a>
        in <a href="/category?id=<?= e($post['category_id']) ?>"><?= e($post['category']) ?></a>
        · <?= e($post['views']) ?> views
        <?php if ($post['status'] !== 'published'): ?>· <strong>draft</strong><?php endif ?>
    </p>
    <?= nl2br(e($post['content'])) ?>
    <?php if ($post['tags'] !== ''): ?>
        <p class="tags">Tags: <?= e($post['tags']) ?></p>
    <?php endif ?>
</article>

<section>
    <h2>Comments (<?= count($comments) ?>)</h2>
    <?php foreach ($comments as $comment): ?>
        <div class="comment">
            <strong><?= e($comment['author']) ?></strong> <small><?= e($comment['created_at']) ?></small>
            <p><?= nl2br(e($comment['content'])) ?></p>
        </div>
    <?php endforeach ?>

    <?php if ($post['status'] === 'published'): ?>
        <h3>Leave a comment</h3>
        <form method="post" action="/post?id=<?= e($post['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label>Name <input name="author" value="<?= e($form['author'] ?? $user['username'] ?? '') ?>" maxlength="100" required></label>
            <?= fieldError($errors ?? [], 'author') ?>
            <label>Email <input type="email" name="email" value="<?= e($form['email'] ?? $user['email'] ?? '') ?>" required></label>
            <?= fieldError($errors ?? [], 'email') ?>
            <label>Comment <textarea name="content" maxlength="2000" required><?= e($form['content'] ?? '') ?></textarea></label>
            <?= fieldError($errors ?? [], 'content') ?>
            <button>Post comment</button>
        </form>
    <?php endif ?>
</section>
