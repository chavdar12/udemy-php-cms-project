<h1>Your profile</h1>
<form method="post" action="/admin/profile">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <p>Username: <strong><?= e($profile['username']) ?></strong> (<?= e($profile['role']) ?>)</p>
    <label>First name <input name="first_name" value="<?= e($profile['first_name']) ?>" maxlength="100"></label>
    <?= fieldError($errors ?? [], 'first_name') ?>
    <label>Last name <input name="last_name" value="<?= e($profile['last_name']) ?>" maxlength="100"></label>
    <?= fieldError($errors ?? [], 'last_name') ?>
    <label>Email <input type="email" name="email" value="<?= e($profile['email']) ?>" required></label>
    <?= fieldError($errors ?? [], 'email') ?>
    <label>New password <input type="password" name="password" placeholder="Leave empty to keep it"></label>
    <?= fieldError($errors ?? [], 'password') ?>
    <button>Save</button>
</form>
