<?php

namespace App\Enums;

/** PRD 00 §36 — machine readable error code pada response envelope. */
enum ErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case DuplicateResource = 'DUPLICATE_RESOURCE';
    case Conflict = 'CONFLICT';
    case InvalidStateTransition = 'INVALID_STATE_TRANSITION';
    case TooManyRequests = 'TOO_MANY_REQUESTS';
    case ServerError = 'SERVER_ERROR';

    public function httpStatus(): int
    {
        return match ($this) {
            self::ValidationError => 422,
            self::Unauthorized => 401,
            self::Forbidden => 403,
            self::NotFound => 404,
            self::DuplicateResource, self::Conflict, self::InvalidStateTransition => 409,
            self::TooManyRequests => 429,
            self::ServerError => 500,
        };
    }
}
