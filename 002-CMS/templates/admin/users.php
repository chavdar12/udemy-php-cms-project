<h1>Users</h1>
<table>
    <tr><th>Username</th><th>Email</th><th>Role</th><th></th></tr>
    <?php foreach ($users as $row): ?>
        <tr>
            <td><?= e($row['username']) ?></td>
            <td><?= e($row['email']) ?></td>
            <td>
                <form method="post" action="/admin/users/role" class="inline">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                    <select name="role" aria-label="Role">
                        <?php foreach (['subscriber', 'admin'] as $role): ?>
                            <option<?= $row['role'] === $role ? ' selected' : '' ?>><?= $role ?></option>
                        <?php endforeach ?>
                    </select>
                    <button>Change</button>
                </form>
            </td>
            <td><?= deleteButton('/admin/users/delete', $row['id'], $csrf) ?></td>
        </tr>
    <?php endforeach ?>
</table>

<h2>Add user</h2>
<form method="post" action="/admin/users">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <label>Username <input name="username" value="<?= e($form['username'] ?? '') ?>" required></label>
    <?= fieldError($errors ?? [], 'username') ?>
    <label>Email <input type="email" name="email" value="<?= e($form['email'] ?? '') ?>" required></label>
    <?= fieldError($errors ?? [], 'email') ?>
    <label>Password <input type="password" name="password" minlength="8" required></label>
    <?= fieldError($errors ?? [], 'password') ?>
    <label>Role
        <select name="role">
            <option>subscriber</option>
            <option>admin</option>
        </select>
    </label>
    <?= fieldError($errors ?? [], 'role') ?>
    <button>Add user</button>
</form>
