<?php

use App\Http\Controllers\Api\V1\AmilController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MuzakiController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\OrganizationMemberController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ZakatController;
use Illuminate\Support\Facades\Route;

/*
 | PRD 00 §16 — seluruh endpoint berada di bawah /api/v1 (prefix di bootstrap/app.php).
 | Permission dicek middleware `permission` (PRD 01 §27) dan organization scope
 | oleh middleware `organization.context` (PRD 02 §15).
 */

Route::get('/health', fn () => ['data' => ['status' => 'ok'], 'meta' => (object) []]);

// ---------------------------------------------------------------- publik

// PRD 01 §20 dan CLAUDE.md §36 — endpoint rawan brute force dibatasi.
Route::middleware('throttle:10,1')->prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/accept-invitation', [AuthController::class, 'acceptInvitation']);
});

// --------------------------------------------------------------- terproteksi

Route::middleware(['auth:sanctum', 'organization.context'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/leave-impersonation', [AuthController::class, 'leaveImpersonation']);
        Route::post('/switch-organization', [OrganizationController::class, 'switch']);

        // PRD 01 §36 — session management milik user sendiri, tanpa permission khusus.
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions', [SessionController::class, 'destroyOthers']);
        Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy']);
    });

    // ------------------------------------------------------------- users

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create');
        Route::get('/{userId}', [UserController::class, 'show'])->middleware('permission:users.view');
        Route::patch('/{userId}', [UserController::class, 'update'])->middleware('permission:users.update');
        Route::put('/{userId}/roles', [UserController::class, 'syncRoles'])->middleware('permission:users.update');

        Route::middleware('permission:users.update')->group(function () {
            Route::post('/{userId}/activate', [UserController::class, 'activate']);
            Route::post('/{userId}/deactivate', [UserController::class, 'deactivate']);
            Route::post('/{userId}/suspend', [UserController::class, 'suspend']);
            Route::post('/{userId}/unlock', [UserController::class, 'unlock']);
        });

        Route::post('/{userId}/impersonate', [UserController::class, 'impersonate'])
            ->middleware('permission:users.impersonate');
    });

    // ------------------------------------------------------ roles & permissions

    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view');

    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create');
        Route::get('/{roleId}', [RoleController::class, 'show'])->middleware('permission:roles.view');
        Route::patch('/{roleId}', [RoleController::class, 'update'])->middleware('permission:roles.update');
        Route::put('/{roleId}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.update');
    });

    // ------------------------------------------------------------ organizations

    // Daftar organisasi yang boleh dipilih user, tanpa permission khusus:
    // isinya sudah dibatasi membership user itu sendiri (PRD 02 §26).
    Route::get('/organizations/available', [OrganizationController::class, 'available']);

    Route::prefix('organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->middleware('permission:organizations.view');
        Route::post('/', [OrganizationController::class, 'store'])->middleware('permission:organizations.create');
        Route::get('/{organizationId}', [OrganizationController::class, 'show'])->middleware('permission:organizations.view');
        Route::patch('/{organizationId}', [OrganizationController::class, 'update'])->middleware('permission:organizations.update');
        Route::get('/{organizationId}/children', [OrganizationController::class, 'children'])->middleware('permission:organizations.view');

        Route::middleware('permission:organizations.update')->group(function () {
            Route::post('/{organizationId}/activate', [OrganizationController::class, 'activate']);
            Route::post('/{organizationId}/deactivate', [OrganizationController::class, 'deactivate']);
            Route::post('/{organizationId}/suspend', [OrganizationController::class, 'suspend']);
        });

        Route::get('/{organizationId}/members', [OrganizationMemberController::class, 'index'])->middleware('permission:members.view');
        Route::post('/{organizationId}/members', [OrganizationMemberController::class, 'store'])->middleware('permission:members.create');

        Route::middleware('permission:members.update')->group(function () {
            Route::patch('/{organizationId}/members/{memberId}', [OrganizationMemberController::class, 'update']);
            Route::post('/{organizationId}/members/{memberId}/activate', [OrganizationMemberController::class, 'activate']);
            Route::post('/{organizationId}/members/{memberId}/deactivate', [OrganizationMemberController::class, 'deactivate']);
            Route::post('/{organizationId}/members/{memberId}/terminate', [OrganizationMemberController::class, 'terminate']);
        });
    });

    // ------------------------------------------------------------------ amils

    Route::prefix('amils')->group(function () {
        Route::get('/', [AmilController::class, 'index'])->middleware('permission:amils.view');
        Route::post('/', [AmilController::class, 'store'])->middleware('permission:amils.create');
        Route::get('/{amilId}', [AmilController::class, 'show'])->middleware('permission:amils.view');
        Route::patch('/{amilId}', [AmilController::class, 'update'])->middleware('permission:amils.update');

        Route::middleware('permission:amils.update')->group(function () {
            Route::post('/{amilId}/activate', [AmilController::class, 'activate']);
            Route::post('/{amilId}/deactivate', [AmilController::class, 'deactivate']);
            Route::post('/{amilId}/suspend', [AmilController::class, 'suspend']);
            Route::post('/{amilId}/end', [AmilController::class, 'end']);
        });

        Route::get('/{amilId}/assignments', [AmilController::class, 'assignments'])->middleware('permission:assignments.view');
        Route::post('/{amilId}/assignments', [AmilController::class, 'storeAssignment'])->middleware('permission:assignments.create');
    });

    Route::post('/amil-assignments/{assignmentId}/end', [AmilController::class, 'endAssignment'])
        ->middleware('permission:assignments.update');

    Route::prefix('muzakis')->group(function () {
        Route::get('/', [MuzakiController::class, 'index'])->middleware('permission:muzaki.view');
        Route::post('/', [MuzakiController::class, 'store'])->middleware('permission:muzaki.create');
        Route::get('/{muzakiId}/contribution-summary', [MuzakiController::class, 'summary'])->middleware('permission:muzaki.view');
        Route::get('/{muzakiId}', [MuzakiController::class, 'show'])->middleware('permission:muzaki.view');
        Route::patch('/{muzakiId}', [MuzakiController::class, 'update'])->middleware('permission:muzaki.update');
        Route::post('/{muzakiId}/activate', [MuzakiController::class, 'activate'])->middleware('permission:muzaki.activate');
        Route::post('/{muzakiId}/deactivate', [MuzakiController::class, 'deactivate'])->middleware('permission:muzaki.deactivate');
        Route::post('/{muzakiId}/archive', [MuzakiController::class, 'archive'])->middleware('permission:muzaki.archive');
    });

    Route::prefix('zakat')->group(function () {
        Route::get('/categories', [ZakatController::class, 'categories'])->middleware('permission:zakat.view');
        Route::post('/categories', [ZakatController::class, 'storeCategory'])->middleware('permission:zakat.category.manage');
        Route::get('/types', [ZakatController::class, 'types'])->middleware('permission:zakat.view');
        Route::post('/types', [ZakatController::class, 'storeType'])->middleware('permission:zakat.type.create');
        Route::get('/rules', [ZakatController::class, 'rules'])->middleware('permission:zakat.view');
        Route::post('/rules', [ZakatController::class, 'storeRule'])->middleware('permission:zakat.rule.create');
        Route::get('/rules/{ruleId}', [ZakatController::class, 'showRule'])->middleware('permission:zakat.view');
        Route::post('/rules/{ruleId}/activate', [ZakatController::class, 'activate'])->middleware('permission:zakat.rule.activate');
        Route::post('/rules/{ruleId}/expire', [ZakatController::class, 'expire'])->middleware('permission:zakat.rule.expire');
        Route::post('/rules/{ruleId}/archive', [ZakatController::class, 'archive'])->middleware('permission:zakat.rule.archive');
    });
});
