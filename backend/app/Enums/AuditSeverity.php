<?php

namespace App\Enums;

/** PRD 17M §22. */
enum AuditSeverity: string
{
    case Info = 'INFO';
    case Notice = 'NOTICE';
    case Warning = 'WARNING';
    case Critical = 'CRITICAL';
}
