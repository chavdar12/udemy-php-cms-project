<h1><?= e($heading) ?></h1>
<?php if ($posts === []): ?>
    <p>No posts found.</p>
<?php endif ?>
<?php foreach ($posts as $post): ?>
    <article>
        <h2><a href="/post?id=<?= e($post['id']) ?>"><?= e($post['title']) ?></a></h2>
        <p class="meta">
            by <a href="/author?id=<?= e($post['user_id']) ?>"><?= e($post['author']) ?></a>
            in <a href="/category?id=<?= e($post['category_id']) ?>"><?= e($post['category']) ?></a>
            on <?= e(substr($post['created_at'], 0, 10)) ?>
        </p>
        <p><?= e(mb_strimwidth($post['content'], 0, 200, '…')) ?></p>
    </article>
<?php endforeach ?>
<?php if (($pages ?? 1) > 1): ?>
    <nav class="pager" aria-label="Pages">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
                <span aria-current="page"><?= $i ?></span>
            <?php else: ?>
                <a href="/?page=<?= $i ?>"><?= $i ?></a>
            <?php endif ?>
        <?php endfor ?>
    </nav>
<?php endif ?>
