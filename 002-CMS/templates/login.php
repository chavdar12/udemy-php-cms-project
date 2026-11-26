<h1>Log in</h1>
<form method="post" action="/login">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label>Username or email <input name="username" value="<?= e($form['username'] ?? '') ?>" required></label>
    <label>Password <input type="password" name="password" required></label>
    <?= fieldError($errors ?? [], 'login') ?>
    <button>Log in</button>
</form>
