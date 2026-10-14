<?php

namespace App\Services\AI\Exceptions;

use Exception;

class NvidiaNimException extends Exception
{
    protected string $errorCategory;

    public function __construct(string $message, string $errorCategory = 'general', int $code = 0, ?\Throwable $previous = null)
    {
        $this->errorCategory = $errorCategory;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCategory(): string
    {
        return $this->errorCategory;
    }
}
