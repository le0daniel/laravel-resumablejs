<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 04.06.19
 * Time: 16:56
 */

namespace le0daniel\LaravelResumableJs\Upload;

use le0daniel\LaravelResumableJs\Contracts\FileCombiner;
use Symfony\Component\Process\Process;

class CatFileCombiner implements FileCombiner
{

    /**
     * Combine the files together
     *
     * @param array $filesToCombine
     * @param string $absoluteOutputPath
     * @return bool
     */
    public function combineFiles(array $filesToCombine, string $absoluteOutputPath): bool
    {
        // Merge All files together
        $command = ['cat'];
        array_push($command, ...$filesToCombine);
        $command[] = '>';
        $command[] = $absoluteOutputPath;

        // Set the timeout depending on the mode the app is running
        // Larger files are always processed in the background
        $timeout = 60;
        if (app()->runningInConsole()) {
            $timeout = 240;
        }

        // Run the command
        (new Process($command))->setTimeout($timeout)->mustRun();

        return file_exists($absoluteOutputPath);
    }
}
