<?php
declare(strict_types=1);

namespace le0daniel\LaravelResumableJs\Utility;

final class Files
{

    public static function getExtension(string $filename): string {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

}
