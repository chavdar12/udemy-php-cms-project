<?php

declare(strict_types=1);

require __DIR__ . '/../check.php';
require __DIR__ . '/form.php';

$ok = validateSignup(['name' => '  Ana ', 'email' => 'ana@example.com', 'color' => 'blue']);
check($ok['errors'] === [] && $ok['data']['name'] === 'Ana', 'valid input is trimmed and accepted');

$bad = validateSignup(['name' => ['array'], 'email' => 'nope', 'color' => 'pink']);
check(array_keys($bad['errors']) === ['name', 'email', 'color'], 'non-string and invalid fields are rejected');
check(validateSignup([])['errors'] !== [], 'missing fields are rejected');

$html = renderSignupForm(['name' => '<script>x</script>', 'color' => 'green'], ['email' => 'Enter a valid email address.']);
check(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'), 'old values are escaped');
check(str_contains($html, '<option selected>green</option>'), 'the chosen color stays selected');
check(str_contains($html, 'Enter a valid email address.'), 'errors are shown');
