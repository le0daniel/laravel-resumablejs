<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 04.06.19
 * Time: 16:57
 */

namespace le0daniel\Laravel\ResumableJs\Upload;


interface FileCombinerContract
{
    public function combineFiles(array $filesToCombine, string $absoluteOutputPath):bool;
}