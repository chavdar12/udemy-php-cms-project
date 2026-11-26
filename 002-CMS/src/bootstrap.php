<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Cms\\')) {
        require __DIR__ . '/' . substr($class, 4) . '.php';
    }
});

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The validation message for one form field, if there is one. */
function fieldError(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<p class="error">' . e($errors[$field]) . '</p>' : '';
}

/** A small POST form that deletes one row; carries the CSRF token like every other form. */
function deleteButton(string $action, int|string $id, string $csrf): string
{
    return sprintf(
        '<form method="post" action="%s" class="inline"><input type="hidden" name="_csrf" value="%s">'
        . '<input type="hidden" name="id" value="%s"><button>Delete</button></form>',
        e($action),
        e($csrf),
        e($id),
    );
}
