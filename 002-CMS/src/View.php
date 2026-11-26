<?php

declare(strict_types=1);

namespace Cms;

final class View
{
    /** Renders templates/$template.php inside templates/layout.php. Templates escape every value with e(). */
    public static function render(string $template, array $data): string
    {
        $content = self::include($template, $data);
        return self::include('layout', [...$data, 'content' => $content]);
    }

    private static function include(string $template, array $data): string
    {
        extract($data);
        ob_start();
        require __DIR__ . "/../templates/$template.php";
        return (string) ob_get_clean();
    }
}
