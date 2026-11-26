<?php

declare(strict_types=1);

interface Shape
{
    public function area(): float;
}

abstract class NamedShape implements Shape
{
    public function describe(): string
    {
        return sprintf('%s with area %.2f', static::class, $this->area());
    }
}

final class Rectangle extends NamedShape
{
    public function __construct(private float $width, private float $height)
    {
        if ($width <= 0 || $height <= 0) {
            throw new InvalidArgumentException('Sides must be positive');
        }
    }

    public function area(): float
    {
        return $this->width * $this->height;
    }
}

final class Circle extends NamedShape
{
    public function __construct(private float $radius)
    {
        if ($radius <= 0) {
            throw new InvalidArgumentException('Radius must be positive');
        }
    }

    public function area(): float
    {
        return M_PI * $this->radius ** 2;
    }
}

class BankAccount
{
    private static int $opened = 0;
    private float $balance = 0;

    public function __construct(public readonly string $owner)
    {
        self::$opened++;
    }

    public static function opened(): int
    {
        return self::$opened;
    }

    public function deposit(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Deposit must be positive');
        }
        $this->balance += $amount;
    }

    public function withdraw(float $amount): void
    {
        if ($amount <= 0 || $amount > $this->balance) {
            throw new InvalidArgumentException('Invalid withdrawal');
        }
        $this->balance -= $amount;
    }

    public function balance(): float
    {
        return $this->balance;
    }
}
