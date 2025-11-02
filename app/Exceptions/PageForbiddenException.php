<?php

declare(strict_types=1);

namespace App\Exceptions;

use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\HTTPExceptionInterface;

/**
 * Represents a 403 Forbidden HTTP error within the application.
 */
class PageForbiddenException extends \RuntimeException implements HTTPExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * HTTP status code.
     *
     * @var int
     */
    protected $code = 403;

    public static function forPageForbidden(?string $message = null): self
    {
        return new self($message ?? 'Acceso denegado.');
    }
}

