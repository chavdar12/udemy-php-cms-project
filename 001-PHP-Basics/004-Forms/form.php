<?php

declare(strict_types=1);

const COLORS = ['red', 'green', 'blue'];

/**
 * Validates submitted form fields. Never trusts the input: every field may be missing or not a string.
 *
 * @return array{data: array<string, string>, errors: array<string, string>}
 */
function validateSignup(array $input): array
{
    $field = fn(string $name) => is_string($input[$name] ?? null) ? trim($input[$name]) : '';
    $data = ['name' => $field('name'), 'email' => $field('email'), 'color' => $field('color')];
    $errors = [];

    if ($data['name'] === '' || mb_strlen($data['name']) > 50) {
        $errors['name'] = 'Name is required (up to 50 characters).';
    }
    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!in_array($data['color'], COLORS, true)) {
        $errors['color'] = 'Pick one of the listed colors.';
    }
    return ['data' => $data, 'errors' => $errors];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Renders the form, re-filling submitted values (escaped) next to their errors. */
function renderSignupForm(array $data = [], array $errors = []): string
{
    $html = '<form method="post">';
    foreach (['name' => 'Name', 'email' => 'Email'] as $name => $label) {
        $html .= sprintf('<label>%s <input name="%s" value="%s"></label>', $label, $name, e($data[$name] ?? ''));
        $html .= isset($errors[$name]) ? '<p class="error">' . e($errors[$name]) . '</p>' : '';
    }
    $html .= '<select name="color">';
    foreach (COLORS as $color) {
        $selected = ($data['color'] ?? '') === $color ? ' selected' : '';
        $html .= "<option$selected>$color</option>";
    }
    $html .= '</select>' . (isset($errors['color']) ? '<p class="error">' . e($errors['color']) . '</p>' : '');
    return $html . '<button>Sign up</button></form>';
}
