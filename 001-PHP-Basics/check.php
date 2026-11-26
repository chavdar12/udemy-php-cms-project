<?php

declare(strict_types=1);

// Tiny assertion helper shared by the lesson tests: prints failures, exits 1 if any.
function check(bool $ok, string $name): void
{
    global $passed, $failed;
    if ($ok) {
        $passed = ($passed ?? 0) + 1;
    } else {
        $failed = ($failed ?? 0) + 1;
        echo "FAIL: $name\n";
    }
}

register_shutdown_function(function (): void {
    global $passed, $failed;
    echo ($passed ?? 0) . ' passed, ' . ($failed ?? 0) . " failed\n";
    if ($failed ?? 0) {
        exit(1);
    }
});
