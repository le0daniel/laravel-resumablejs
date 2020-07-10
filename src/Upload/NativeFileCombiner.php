<?php

namespace le0daniel\Laravel\ResumableJs\Upload;

final class NativeFileCombiner
{
    public function combineFiles(array $filesToCombine, string $absoluteOutputPath): bool
    {
        $outputStream = self::openResource($absoluteOutputPath, 'w+');

        foreach ($filesToCombine as $file) {
            $fileStream = self::openResource($file, 'r+');
            stream_copy_to_stream($fileStream, $outputStream);
            self::closeResource($fileStream);
        }

        self::closeResource($outputStream);
        return true;
    }

    private static function closeResource($resource): void
    {
        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    private static function openResource(string $filePath, string $mode)
    {
        $fileStream = fopen($filePath, $mode);
        if (!is_resource($fileStream)) {
            throw new \Exception("Could not open file ({$filePath}) in {$mode} mode");
        }
        return $fileStream;
    }
}
