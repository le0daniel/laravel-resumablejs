<?php

declare(strict_types=1);

namespace le0daniel\Laravel\ResumableJs\Utility;

use Illuminate\Support\Str;

final class Tokens
{
    private const DEFAULT_TOKEN_LENGTH = 64;

    public static function generateRandom(int $length = self::DEFAULT_TOKEN_LENGTH): string
    {
        return strtolower(Str::random($length));
    }

}
