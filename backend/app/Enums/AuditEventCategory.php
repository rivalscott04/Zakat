<?php

namespace App\Enums;

/** PRD 17D §9. */
enum AuditEventCategory: string
{
    case Authentication = 'AUTHENTICATION';
    case Authorization = 'AUTHORIZATION';
    case Create = 'CREATE';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
    case Restore = 'RESTORE';
    case Approval = 'APPROVAL';
    case Rejection = 'REJECTION';
    case Payment = 'PAYMENT';
    case Collection = 'COLLECTION';
    case Distribution = 'DISTRIBUTION';
    case Assessment = 'ASSESSMENT';
    case Program = 'PROGRAM';
    case Document = 'DOCUMENT';
    case Banking = 'BANKING';
    case Accounting = 'ACCOUNTING';
    case Notification = 'NOTIFICATION';
    case Configuration = 'CONFIGURATION';
    case Security = 'SECURITY';
    case System = 'SYSTEM';
    case Other = 'OTHER';
}
