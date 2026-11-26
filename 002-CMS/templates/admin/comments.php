<h1>Comments</h1>
<table>
    <tr><th>Author</th><th>Comment</th><th>Post</th><th>Status</th><th></th></tr>
    <?php foreach ($comments as $comment): ?>
        <tr>
            <td><?= e($comment['author']) ?><br><small><?= e($comment['email']) ?></small></td>
            <td><?= e($comment['content']) ?></td>
            <td><a href="/post?id=<?= e($comment['post_id']) ?>"><?= e($comment['post_title']) ?></a></td>
            <td><?= e($comment['status']) ?></td>
            <td>
                <form method="post" action="/admin/comments/status" class="inline">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= e($comment['id']) ?>">
                    <?php $next = $comment['status'] === 'approved' ? 'pending' : 'approved' ?>
                    <input type="hidden" name="status" value="<?= $next ?>">
                    <button><?= $next === 'approved' ? 'Approve' : 'Unapprove' ?></button>
                </form>
                <?= deleteButton('/admin/comments/delete', $comment['id'], $csrf) ?>
            </td>
        </tr>
    <?php endforeach ?>
</table>
