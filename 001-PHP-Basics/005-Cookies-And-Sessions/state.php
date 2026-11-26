<?php

declare(strict_types=1);

const THEMES = ['light', 'dark'];

/** Counts page views in the session (an array, so it can be tested without a real session). */
function countVisit(array &$session): int
{
    $session['visits'] = ($session['visits'] ?? 0) + 1;
    return $session['visits'];
}

/** Reads the theme cookie, falling back to "light" for missing or tampered values. */
function themeFromCookies(array $cookies): string
{
    $theme = $cookies['theme'] ?? null;
    return in_array($theme, THEMES, true) ? $theme : 'light';
}

/** Arguments for setcookie(): one year, not readable from JavaScript, same-site only. */
function themeCookie(string $theme, int $now): array
{
    if (!in_array($theme, THEMES, true)) {
        throw new InvalidArgumentException("Unknown theme: $theme");
    }
    return ['theme', $theme, ['expires' => $now + 365 * 24 * 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']];
}

function logout(array &$session): void
{
    $session = [];
}
