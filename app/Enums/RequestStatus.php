<?php

namespace App\Enums;

enum RequestStatus: int
{
    case OPEN = 1;
    case CLOSED = 2;

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'green',
            self::CLOSED => 'red',
        };
    }
}