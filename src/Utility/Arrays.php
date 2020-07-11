<?php

declare(strict_types=1);

namespace le0daniel\LaravelResumableJs\Utility;

final class Arrays
{

    public static function filterNullValues(array $array): array
    {
        return array_filter($array, fn($item) => $item !== null);
    }

}
