<?php

namespace le0daniel\LaravelResumableJs\Upload;

use Exception;
use Throwable;

final class UploadProcessingException extends Exception
{
    private ?string $userMessage = null;

    private function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getUserMessage(): ?string {
        return $this->userMessage;
    }

    public static function with(?string $userShowableMessage, string $internalMessage, Throwable $previous = null): self {
        $instance = new self($internalMessage, $previous);
        $instance->userMessage = $userShowableMessage;
        return $instance;
    }

}
