<?php

declare(strict_types=1);

// Run with: php -S localhost:8000 -t 005-Cookies-And-Sessions
require __DIR__ . '/state.php';

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
if (isset($_GET['theme']) && in_array($_GET['theme'], THEMES, true)) {
    setcookie(...themeCookie($_GET['theme'], time()));
    $_COOKIE['theme'] = $_GET['theme'];
}
$visits = countVisit($_SESSION);
$theme = themeFromCookies($_COOKIE);
?>
<!doctype html>
<body class="<?= $theme ?>">
<p>You have opened this page <?= $visits ?> time(s) in this session.</p>
<p>Theme: <a href="?theme=light">light</a> · <a href="?theme=dark">dark</a></p>
</body>
