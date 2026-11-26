<?php

declare(strict_types=1);

namespace Cms;

final readonly class Response
{
    public function __construct(public int $status, public string $body = '', public array $headers = []) {}

    public static function redirect(string $to): self
    {
        return new self(303, '', ['Location' => $to]);
    }
}
