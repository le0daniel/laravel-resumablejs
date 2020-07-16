<?php

declare(strict_types=1);

namespace le0daniel\LaravelResumableJs\Utility;

final class Resources
{

    /**
     * @param string $path
     * @param string $mode
     * @return resource
     */
    public static function open(string $path, string $mode)
    {
        $resource = fopen($path, $mode);
        if (!is_resource($resource)) {
            throw new \RuntimeException("Could not open resource {$path} in mode: {$mode}");
        }

        return $resource;
    }

    public static function close(...$resources): void
    {
        foreach ($resources as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Combine multiple files into one new file.
     *
     * @param string|string[] $sourcePaths
     * @param string $destinationPath
     * @param string $fromMode
     * @param string $destinationOpenMode
     * @throws \Exception
     */
    public static function combine($sourcePaths, string $destinationPath, string $fromMode = 'r', string $destinationOpenMode = 'w+'): void
    {
        $sourcePaths = is_array($sourcePaths) ? $sourcePaths : [$sourcePaths];

        try {
            $destinationResource = self::open($destinationPath, $destinationOpenMode);

            foreach ($sourcePaths as $filePath) {
                $sourceResource = self::open($filePath, $fromMode);

                if (stream_copy_to_stream($sourceResource, $destinationResource) === false) {
                    throw new \RuntimeException("Failed to copy from {$filePath}[{$fromMode}] to {$destinationPath}[{$destinationOpenMode}]");
                }

                self::close($sourceResource);
            }

            self::close($destinationResource);
        } catch (\Exception $exception) {
            self::close($sourceResource ?? null, $destinationResource ?? null);
            throw $exception;
        }
    }

}
