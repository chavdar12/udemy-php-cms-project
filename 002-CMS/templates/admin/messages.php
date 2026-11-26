<h1>Messages</h1>
<?php if ($messages === []): ?>
    <p>No messages yet.</p>
<?php endif ?>
<?php foreach ($messages as $message): ?>
    <article>
        <h2><?= e($message['subject']) ?></h2>
        <p class="meta">From <?= e($message['name']) ?> &lt;<?= e($message['email']) ?>&gt; on <?= e($message['created_at']) ?></p>
        <p><?= nl2br(e($message['body'])) ?></p>
    </article>
<?php endforeach ?>
