<h1>Dashboard</h1>
<dl class="counts">
    <?php foreach ($counts as $label => $count): ?>
        <div><dt><?= e($label) ?></dt><dd><?= e($count) ?></dd></div>
    <?php endforeach ?>
</dl>
