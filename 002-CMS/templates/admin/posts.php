<h1>Posts</h1>
<p><a href="/admin/posts/new">New post</a></p>
<table>
    <tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Views</th><th></th></tr>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><a href="/post?id=<?= e($post['id']) ?>"><?= e($post['title']) ?></a></td>
            <td><?= e($post['category']) ?></td>
            <td><?= e($post['author']) ?></td>
            <td><?= e($post['status']) ?></td>
            <td><?= e($post['views']) ?></td>
            <td>
                <a href="/admin/posts/edit?id=<?= e($post['id']) ?>">Edit</a>
                <?= deleteButton('/admin/posts/delete', $post['id'], $csrf) ?>
            </td>
        </tr>
    <?php endforeach ?>
</table>
