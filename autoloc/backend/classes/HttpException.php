<?php
declare(strict_types=1);

namespace AutoLoc\Classes;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(string $message, private int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int { return $this->statusCode; }
}
