<?php

declare(strict_types=1);

namespace le0daniel\LaravelResumableJs\Utility;

use Illuminate\Support\Str;
use RuntimeException;

final class Files
{

    public static function getExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    private static function getTmpDir(): string
    {
        return rtrim(config('resumablejs.tmp_directory'), '/');
    }

    public static function computeChunkFileName(string $token, int $chunkNumber): string
    {
        $directory = self::getTmpDir();
        $folder = substr($token, 0, 32);
        return "{$directory}/{$folder}/chunk-{$chunkNumber}";
    }

    public static function chunkExists(string $token, int $chunkNumber): bool
    {
        return file_exists(self::computeChunkFileName($token, $chunkNumber));
    }

    public static function writeChunk(string $pathToContentFile, string $token, int $chunkNumber): string
    {
        $filePath = self::computeChunkFileName($token, $chunkNumber);
        Directories::makeRecursive(dirname($filePath));

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        Resources::combine($pathToContentFile, $filePath);

        return $filePath;
    }

    public static function tmp(): string
    {
        $directory = self::getTmpDir();
        $fileName = Str::random(32);
        return "{$directory}/{$fileName}.tmp";
    }

    public static function deleteExisting(string ...$files): void {
        foreach ($files as $file){
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

}
