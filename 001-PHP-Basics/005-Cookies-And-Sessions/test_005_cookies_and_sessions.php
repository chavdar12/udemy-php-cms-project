<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/state.php';

$session = [];
countVisit($session);
check(countVisit($session) === 2, 'visits are counted per session');
logout($session);
check(countVisit($session) === 1, 'logout clears the session');

check(themeFromCookies(['theme' => 'dark']) === 'dark', 'reads a valid theme');
check(themeFromCookies(['theme' => '"><script>']) === 'light', 'ignores a tampered cookie');
check(themeFromCookies([]) === 'light', 'defaults to light');

[$name, $value, $options] = themeCookie('dark', 1000);
check($name === 'theme' && $value === 'dark' && $options['expires'] === 1000 + 31536000 && $options['httponly'], 'cookie options');
try {
    themeCookie('neon', 0);
    check(false, 'unknown theme is rejected');
} catch (InvalidArgumentException) {
    check(true, 'unknown theme is rejected');
}
