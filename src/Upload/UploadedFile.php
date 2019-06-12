<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 12.06.19
 * Time: 09:23
 */

namespace le0daniel\Laravel\ResumableJs\Upload;


class UploadedFile extends \SplFileInfo
{
    /** @var string */
    protected $mimeType;

    /**
     * Get the mime type of the file
     *
     * @return string
     */
    public function getMimeType(): string
    {
        if (!isset($this->mimeType)) {
            $finfo = new \finfo(FILEINFO_MIME);
            $resource = fopen($this->getRealPath(), 'r+');
            $this->mimeType = $finfo->buffer(fread($resource, 1024), FILEINFO_MIME_TYPE);
            fclose($resource);
        }
        return $this->mimeType;
    }

}