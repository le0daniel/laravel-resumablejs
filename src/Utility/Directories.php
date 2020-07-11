<?php

declare(strict_types=1);

namespace le0daniel\LaravelResumableJs\Utility;

final class Directories
{

    public static function makeRecursive(string $directory): void
    {
        if (file_exists($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }

}
