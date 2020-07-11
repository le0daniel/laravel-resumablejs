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

    public static function getChunkFileName(string $token, int $chunkNumber): string
    {
        $directory = self::getTmpDir();
        $folder = substr($token, 0, 32);

        return "{$directory}/{$folder}/chunk-{$chunkNumber}";
    }

    public static function hasTmpChunkFile(string $token, int $chunkNumber): bool
    {
        return file_exists(self::getChunkFileName($token, $chunkNumber));
    }

    public static function getTmpChunkFileForWriting(string $token, int $chunkNumber): string
    {
        $filePath = self::getChunkFileName($token, $chunkNumber);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        Directories::makeRecursive(dirname($filePath));
        return $filePath;
    }

    public static function tmp(string $extension, bool $createFile = false): string
    {
        $directory = self::getTmpDir();
        $fileName = Str::random(32);
        $filePath = "{$directory}/{$fileName}.{$extension}";

        if ($createFile) {
            touch($filePath);
        }

        return $filePath;
    }

}
