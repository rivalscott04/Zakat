<?php

use Illuminate\Support\Facades\Route;

/*
 | PRD 00 §16 — seluruh endpoint berada di bawah /api/v1 (prefix diatur pada
 | bootstrap/app.php). Permission enforcement memakai middleware `permission`
 | sesuai PRD 01 §27.
 */

Route::get('/health', fn () => ['data' => ['status' => 'ok'], 'meta' => (object) []]);
