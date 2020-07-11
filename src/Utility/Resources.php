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
     * @param string|string[] $from
     * @param string $to
     * @param string $fromMode
     * @param string $toMode
     * @throws \Exception
     */
    public static function combine($from, string $to, string $fromMode = 'r', string $toMode = 'w+'): void
    {
        if (!is_array($from)) $from = [$from];

        try {
            $toResource = self::open($to, $toMode);

            foreach ($from as $filePath) {
                $formResource = self::open($filePath, $fromMode);

                if (stream_copy_to_stream($formResource, $toResource) === false) {
                    throw new \RuntimeException("Failed to copy from {$from}[{$fromMode}] to {$to}[{$toMode}]");
                }
                self::close($formResource);
            }

            self::close($toResource);
        } catch (\Exception $exception) {
            self::close($formResource ?? null, $toResource ?? null);
            throw $exception;
        }
    }

    public static function auto(\Closure $closure, string $mode, string ...$paths)
    {
        $resources = array_map(fn(string $path) => self::open($path, $mode), $paths);
        $closure(...$resources);
        self::close(...$resources);
    }

}
