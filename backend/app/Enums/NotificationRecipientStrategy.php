<?php

namespace App\Enums;

/** PRD 16M §29. */
enum NotificationRecipientStrategy: string
{
    case User = 'user';
    case Role = 'role';
    case Permission = 'permission';
    case OrganizationAdmin = 'organization_admin';
    case SourceOwner = 'source_owner';
    case Custom = 'custom';
}
