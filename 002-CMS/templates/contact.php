<h1>Contact</h1>
<form method="post" action="/contact">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <?php foreach (['name' => 'Name', 'email' => 'Email', 'subject' => 'Subject'] as $field => $label): ?>
        <label><?= $label ?> <input name="<?= $field ?>" value="<?= e($form[$field] ?? '') ?>" required></label>
        <?= fieldError($errors ?? [], $field) ?>
    <?php endforeach ?>
    <label>Message <textarea name="body" required><?= e($form['body'] ?? '') ?></textarea></label>
    <?= fieldError($errors ?? [], 'body') ?>
    <button>Send</button>
</form>
