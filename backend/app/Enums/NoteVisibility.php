<?php

namespace App\Enums;

/** PRD 03 §47 — visibilitas catatan. */
enum NoteVisibility: string
{
    case Internal = 'internal';
    case Restricted = 'restricted';
}
