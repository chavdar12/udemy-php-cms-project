<h1><?= $id === null ? 'New post' : 'Edit post' ?></h1>
<form method="post" action="<?= $id === null ? '/admin/posts/new' : '/admin/posts/edit?id=' . e($id) ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label>Title <input name="title" value="<?= e($form['title'] ?? '') ?>" maxlength="255" required></label>
    <?= fieldError($errors ?? [], 'title') ?>
    <label>Category
        <select name="category_id">
            <?php foreach ($categories as $category): ?>
                <option value="<?= e($category['id']) ?>"<?= (string) ($form['category_id'] ?? '') === (string) $category['id'] ? ' selected' : '' ?>><?= e($category['title']) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <?= fieldError($errors ?? [], 'category_id') ?>
    <label>Status
        <select name="status">
            <?php foreach (['draft', 'published'] as $status): ?>
                <option<?= ($form['status'] ?? 'draft') === $status ? ' selected' : '' ?>><?= $status ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <?= fieldError($errors ?? [], 'status') ?>
    <label>Tags <input name="tags" value="<?= e($form['tags'] ?? '') ?>" maxlength="255"></label>
    <?= fieldError($errors ?? [], 'tags') ?>
    <label>Content <textarea name="content" rows="12" required><?= e($form['content'] ?? '') ?></textarea></label>
    <?= fieldError($errors ?? [], 'content') ?>
    <button>Save</button>
</form>
