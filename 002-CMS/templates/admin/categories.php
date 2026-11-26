<h1>Categories</h1>
<form method="post" action="/admin/categories">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label>New category <input name="title" maxlength="100" required></label>
    <button>Add</button>
</form>
<?= fieldError($errors ?? [], 'title') ?>
<table>
    <tr><th>Title</th><th>Posts</th><th></th></tr>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td>
                <form method="post" action="/admin/categories" class="inline">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= e($category['id']) ?>">
                    <input name="title" value="<?= e($category['title']) ?>" aria-label="Title" maxlength="100" required>
                    <button>Rename</button>
                </form>
            </td>
            <td><?= e($category['posts']) ?></td>
            <td><?= deleteButton('/admin/categories/delete', $category['id'], $csrf) ?></td>
        </tr>
    <?php endforeach ?>
</table>
