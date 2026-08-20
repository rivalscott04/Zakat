<?php

namespace App\Enums;

/** PRD 03 §41 — visibilitas publik kontribusi. */
enum PublicVisibility: string
{
    case Public = 'public';
    case Anonymous = 'anonymous';
    case Private = 'private';
}
