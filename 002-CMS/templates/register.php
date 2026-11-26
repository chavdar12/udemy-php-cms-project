<h1>Register</h1>
<form method="post" action="/register">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label>Username <input name="username" value="<?= e($form['username'] ?? '') ?>" required></label>
    <?= fieldError($errors ?? [], 'username') ?>
    <label>Email <input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" required></label>
    <?= fieldError($errors ?? [], 'email') ?>
    <label>Password <input type="password" name="password" minlength="8" required></label>
    <?= fieldError($errors ?? [], 'password') ?>
    <button>Create account</button>
</form>
