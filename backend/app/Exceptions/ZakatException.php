<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;

/**
 * Business rule violation yang harus dikembalikan ke client dalam error envelope
 * PRD 00 §17. Dilempar dari Service Layer, dirender terpusat di bootstrap/app.php.
 */
class ZakatException extends RuntimeException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /** @param array<string, array<int, string>> $errors */
    public static function conflict(string $message, array $errors = []): self
    {
        return new self(ErrorCode::Conflict, $message, $errors);
    }

    public static function forbidden(string $message): self
    {
        return new self(ErrorCode::Forbidden, $message);
    }

    public static function notFound(string $message): self
    {
        return new self(ErrorCode::NotFound, $message);
    }

    public static function duplicate(string $message): self
    {
        return new self(ErrorCode::DuplicateResource, $message);
    }

    public static function invalidTransition(string $message): self
    {
        return new self(ErrorCode::InvalidStateTransition, $message);
    }
}
