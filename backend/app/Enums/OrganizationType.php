<?php

namespace App\Enums;

/** PRD 02 §6 — tipe organisasi. */
enum OrganizationType: string
{
    case Platform = 'platform';
    case Organization = 'organization';
    case Branch = 'branch';
    case Unit = 'unit';
    case Upz = 'upz';
}
