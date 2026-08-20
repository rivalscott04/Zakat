<?php

use App\Http\Controllers\Api\V1\AccountingController;
use App\Http\Controllers\Api\V1\AmilController;
use App\Http\Controllers\Api\V1\AssessmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\DistributionBatchController;
use App\Http\Controllers\Api\V1\DistributionController;
use App\Http\Controllers\Api\V1\FundController;
use App\Http\Controllers\Api\V1\MustahikController;
use App\Http\Controllers\Api\V1\MuzakiController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\OrganizationMemberController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ZakatCalculationController;
use App\Http\Controllers\Api\V1\ZakatController;
use App\Services\ZakatService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
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

Route::middleware(['auth:sanctum', 'organization.context', 'throttle:api'])->group(function () {

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
        Route::get('/calculations', [ZakatCalculationController::class, 'index'])->middleware('permission:zakat.calculation.view');
        Route::post('/calculations/preview', [ZakatCalculationController::class, 'preview'])->middleware('permission:zakat.calculation.calculate');
        Route::post('/calculations', [ZakatCalculationController::class, 'store'])->middleware('permission:zakat.calculation.create');
        Route::get('/calculations/{id}', [ZakatCalculationController::class, 'show'])->middleware('permission:zakat.calculation.view');
        Route::post('/calculations/{id}/calculate', [ZakatCalculationController::class, 'calculate'])->middleware('permission:zakat.calculation.calculate');
        Route::post('/calculations/{id}/confirm', [ZakatCalculationController::class, 'confirm'])->middleware('permission:zakat.calculation.confirm');
        Route::post('/calculations/{id}/cancel', [ZakatCalculationController::class, 'cancel'])->middleware('permission:zakat.calculation.cancel');
        Route::post('/calculations/{id}/recalculate', [ZakatCalculationController::class, 'recalculate'])->middleware('permission:zakat.calculation.recalculate');
        Route::post('/calculations/{id}/adjustments', [ZakatCalculationController::class, 'adjust'])->middleware('permission:zakat.calculation.adjust');
        Route::post('/calculations/{id}/convert', [ZakatCalculationController::class, 'convert'])->middleware('permission:zakat.calculation.convert');
        Route::get('/categories', [ZakatController::class, 'categories'])->middleware('permission:zakat.view');
        Route::post('/categories', [ZakatController::class, 'storeCategory'])->middleware('permission:zakat.category.manage');
        Route::get('/types', [ZakatController::class, 'types'])->middleware('permission:zakat.view');
        Route::post('/types', [ZakatController::class, 'storeType'])->middleware('permission:zakat.type.create');
        Route::get('/rules', [ZakatController::class, 'rules'])->middleware('permission:zakat.view');
        Route::post('/rules', [ZakatController::class, 'storeRule'])->middleware('permission:zakat.rule.create');
        Route::get('/rules/resolve', [ZakatController::class, 'resolve'])->middleware('permission:zakat.rule.resolve');
        Route::get('/rules/{ruleId}', [ZakatController::class, 'showRule'])->middleware('permission:zakat.view');
        Route::post('/rules/{ruleId}/rates', fn (Request $request, string $ruleId, ZakatService $service) => ApiResponse::data($service->config($ruleId, 'rate', $request->validate(['rate_type' => 'required|string', 'rate_value' => 'required|numeric', 'unit' => 'required|string', 'effective_from' => 'required|date', 'effective_until' => 'nullable|date'])), status: 201))->middleware('permission:zakat.rate.manage');
        Route::post('/rules/{ruleId}/nisab', fn (Request $request, string $ruleId, ZakatService $service) => ApiResponse::data($service->config($ruleId, 'nisab', $request->validate(['nisab_type' => 'required|string', 'reference_type' => 'nullable|string', 'reference_value' => 'nullable|numeric', 'quantity' => 'nullable|numeric', 'unit' => 'nullable|string', 'currency' => 'nullable|string|size:3', 'effective_from' => 'required|date', 'effective_until' => 'nullable|date'])), status: 201))->middleware('permission:zakat.nisab.manage');
        Route::post('/rules/{ruleId}/haul', fn (Request $request, string $ruleId, ZakatService $service) => ApiResponse::data($service->config($ruleId, 'haul', $request->validate(['haul_type' => 'required|string', 'duration' => 'nullable|integer', 'duration_unit' => 'nullable|string', 'calendar_type' => 'nullable|string'])), status: 201))->middleware('permission:zakat.haul.manage');
        Route::post('/rules/{ruleId}/parameters', fn (Request $request, string $ruleId, ZakatService $service) => ApiResponse::data($service->config($ruleId, 'parameter', $request->validate(['parameter_code' => 'required|string', 'name' => 'required|string', 'data_type' => 'required|string', 'is_required' => 'boolean', 'default_value' => 'nullable|array', 'validation_rules' => 'nullable|array'])), status: 201))->middleware('permission:zakat.parameter.manage');
        Route::post('/rules/{ruleId}/formulas', fn (Request $request, string $ruleId, ZakatService $service) => ApiResponse::data($service->config($ruleId, 'formula', $request->validate(['formula_code' => 'required|string|max:80', 'formula_version' => 'required|integer|min:1', 'formula_type' => 'required|string|max:30', 'expression' => 'required|string|max:255', 'input_schema' => 'nullable|array', 'output_schema' => 'nullable|array'])), status: 201))->middleware('permission:zakat.formula.manage');
        Route::post('/reference-values', [ZakatController::class, 'reference'])->middleware('permission:zakat.reference_value.manage');
        Route::post('/rules/{ruleId}/activate', [ZakatController::class, 'activate'])->middleware('permission:zakat.rule.activate');
        Route::post('/rules/{ruleId}/expire', [ZakatController::class, 'expire'])->middleware('permission:zakat.rule.expire');
        Route::post('/rules/{ruleId}/archive', [ZakatController::class, 'archive'])->middleware('permission:zakat.rule.archive');
    });

    Route::prefix('collections')->middleware('throttle:financial')->group(function () {
        Route::get('/summary', [CollectionController::class, 'summary'])->middleware('permission:collection.view');
        Route::get('/', [CollectionController::class, 'index'])->middleware('permission:collection.view');
        Route::post('/', [CollectionController::class, 'store'])->middleware('permission:collection.create_manual');
        Route::post('/from-calculation', [CollectionController::class, 'fromCalculation'])->middleware('permission:collection.create');
        Route::get('/{id}', [CollectionController::class, 'show'])->middleware('permission:collection.view');
        Route::post('/{id}/confirm', [CollectionController::class, 'confirm'])->middleware('permission:collection.confirm');
        Route::post('/{id}/cancel', [CollectionController::class, 'cancel'])->middleware('permission:collection.cancel');
        Route::post('/{id}/reactivate', [CollectionController::class, 'reactivate'])->middleware('permission:collection.reactivate');
        Route::post('/{id}/payments', [CollectionController::class, 'payment'])->middleware('permission:collection.create');
        Route::post('/payments/{paymentId}/verify', [CollectionController::class, 'verifyPayment'])->middleware('permission:collection.verify');
    });

    Route::prefix('funds')->middleware('throttle:financial')->group(function () {
        Route::get('/', [FundController::class, 'index'])->middleware('permission:fund.view');
        Route::post('/', [FundController::class, 'store'])->middleware('permission:fund.create');
        Route::get('/{id}', [FundController::class, 'show'])->middleware('permission:fund.view');
        Route::get('/{id}/balance', [FundController::class, 'balance'])->middleware('permission:fund.balance.view');
        Route::get('/{id}/movements', [FundController::class, 'movements'])->middleware('permission:fund.movement.view');
        Route::post('/{id}/inflow', [FundController::class, 'inflow'])->middleware('permission:fund.movement.create');
        Route::post('/{id}/outflow', [FundController::class, 'outflow'])->middleware('permission:fund.movement.create');
        Route::post('/{id}/allocations', [FundController::class, 'allocation'])->middleware('permission:fund.allocation.create');
        Route::post('/{id}/reservations', [FundController::class, 'reservation'])->middleware('permission:fund.reservation.create');
        Route::post('/{id}/check-availability', [FundController::class, 'availability'])->middleware('permission:fund.balance.view');
        Route::post('/{id}/reconciliations', [FundController::class, 'reconcile'])->middleware('permission:fund.reconciliation.create');
    });
    Route::post('/funds/inflow-from-collection', [FundController::class, 'inflowFromCollection'])->middleware('permission:fund.movement.create');
    Route::post('/fund-adjustments', [FundController::class, 'adjustment'])->middleware('permission:fund.adjustment.create');
    Route::post('/fund-allocations/{id}/approve', [FundController::class, 'approveAllocation'])->middleware('permission:fund.allocation.approve');
    Route::post('/fund-reservations/{id}/release', [FundController::class, 'releaseReservation'])->middleware('permission:fund.reservation.release');
    Route::post('/fund-transfers', [FundController::class, 'transfer'])->middleware('permission:fund.transfer.create');
    Route::post('/fund-transfers/{id}/approve', [FundController::class, 'approveTransfer'])->middleware('permission:fund.transfer.approve');

    Route::prefix('accounting')->middleware('throttle:financial')->group(function () {
        Route::get('/accounts', [AccountingController::class, 'accounts'])->middleware('permission:accounting.account.view');
        Route::post('/accounts', [AccountingController::class, 'createAccount'])->middleware('permission:accounting.account.create');
        Route::get('/periods', [AccountingController::class, 'periods'])->middleware('permission:accounting.period.view');
        Route::post('/periods', [AccountingController::class, 'createPeriod'])->middleware('permission:accounting.period.create');
        Route::post('/periods/{id}/lock', [AccountingController::class, 'lockPeriod'])->middleware('permission:accounting.period.lock');
        Route::post('/periods/{id}/close', [AccountingController::class, 'closePeriod'])->middleware('permission:accounting.period.close');
        Route::get('/journals', [AccountingController::class, 'journals'])->middleware('permission:accounting.journal.view');
        Route::post('/journals', [AccountingController::class, 'createJournal'])->middleware('permission:accounting.journal.create');
        Route::get('/journals/{id}', [AccountingController::class, 'showJournal'])->middleware('permission:accounting.journal.view');
        Route::post('/journals/{id}/submit', [AccountingController::class, 'submit'])->middleware('permission:accounting.journal.submit');
        Route::post('/journals/{id}/approve', [AccountingController::class, 'approve'])->middleware('permission:accounting.journal.approve');
        Route::post('/journals/{id}/post', [AccountingController::class, 'post'])->middleware('permission:accounting.journal.post');
        Route::post('/journals/{id}/reverse', [AccountingController::class, 'reverse'])->middleware('permission:accounting.journal.reverse');
        Route::get('/general-ledger', [AccountingController::class, 'ledger'])->middleware('permission:accounting.ledger.view');
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->middleware('permission:accounting.trial_balance.view');
        Route::post('/events', [AccountingController::class, 'event'])->middleware('permission:accounting.journal.create');
    });
    Route::prefix('mustahiks')->group(function () {
        Route::get('/', [MustahikController::class, 'index'])->middleware('permission:mustahik.view');
        Route::post('/', [MustahikController::class, 'store'])->middleware('permission:mustahik.create');
        Route::post('/check-duplicate', [MustahikController::class, 'duplicate'])->middleware('permission:mustahik.create');
        Route::get('/{id}', [MustahikController::class, 'show'])->middleware('permission:mustahik.view');
        Route::patch('/{id}', [MustahikController::class, 'update'])->middleware('permission:mustahik.update');
        Route::post('/{id}/identities', [MustahikController::class, 'identity'])->middleware('permission:mustahik.identity.verify');
        Route::post('/{id}/addresses', [MustahikController::class, 'address'])->middleware('permission:mustahik.update');
        Route::post('/{id}/asnaf', [MustahikController::class, 'asnaf'])->middleware('permission:mustahik.update');
        Route::post('/{id}/verify', [MustahikController::class, 'verify'])->middleware('permission:mustahik.verification.perform');
    });
    Route::prefix('assessment-requests')->group(function () {
        Route::get('/', [AssessmentController::class, 'requests'])->middleware('permission:assessment.request.view');
        Route::post('/', [AssessmentController::class, 'storeRequest'])->middleware('permission:assessment.request.create');
        Route::get('/{id}', [AssessmentController::class, 'showRequest'])->middleware('permission:assessment.request.view');
        Route::post('/{id}/assign', [AssessmentController::class, 'assign'])->middleware('permission:assessment.request.assign');
        Route::post('/{id}/cancel', [AssessmentController::class, 'cancel'])->middleware('permission:assessment.request.cancel');
    });
    Route::prefix('assessment')->group(function () {
        Route::get('/templates', [AssessmentController::class, 'templates'])->middleware('permission:assessment.template.view');
        Route::post('/templates', [AssessmentController::class, 'storeTemplate'])->middleware('permission:assessment.template.create');
        Route::post('/templates/{id}/publish', [AssessmentController::class, 'publishTemplate'])->middleware('permission:assessment.template.publish');
    });
    Route::prefix('assessments')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->middleware('permission:assessment.view');
        Route::post('/', [AssessmentController::class, 'store'])->middleware('permission:assessment.create');
        Route::get('/{id}', [AssessmentController::class, 'show'])->middleware('permission:assessment.view');
        Route::patch('/{id}', [AssessmentController::class, 'update'])->middleware('permission:assessment.update');
        Route::post('/{id}/submit', [AssessmentController::class, 'submit'])->middleware('permission:assessment.submit');
        Route::post('/{id}/review', [AssessmentController::class, 'review'])->middleware('permission:assessment.review');
        Route::post('/{id}/reassess', [AssessmentController::class, 'reassess'])->middleware('permission:assessment.reassess');
    });
    Route::prefix('programs')->group(function () {
        Route::get('/dashboard', [ProgramController::class, 'dashboard'])->middleware('permission:program.view');
        Route::get('/', [ProgramController::class, 'index'])->middleware('permission:program.view');
        Route::post('/', [ProgramController::class, 'store'])->middleware('permission:program.create');
        Route::get('/categories', [ProgramController::class, 'categories'])->middleware('permission:program.view');
        Route::post('/categories', [ProgramController::class, 'storeCategory'])->middleware('permission:program.category.manage');
        Route::get('/{id}/periods', [ProgramController::class, 'periods'])->middleware('permission:program.view');
        Route::post('/{id}/periods', [ProgramController::class, 'storePeriod'])->middleware('permission:program.update');
        Route::get('/{id}/funds', [ProgramController::class, 'funds'])->middleware('permission:program.view');
        Route::post('/{id}/funds', [ProgramController::class, 'storeFund'])->middleware('permission:program.update');
        Route::get('/{id}/budgets', [ProgramController::class, 'budgets'])->middleware('permission:program.budget.view');
        Route::post('/{id}/budgets', [ProgramController::class, 'budget'])->middleware('permission:program.budget.create');
        Route::get('/{id}/eligibility-rules', [ProgramController::class, 'rules'])->middleware('permission:program.eligibility.view');
        Route::post('/{id}/eligibility-rules', [ProgramController::class, 'storeRule'])->middleware('permission:program.eligibility.manage');
        Route::post('/{id}/evaluate-eligibility', [ProgramController::class, 'evaluate'])->middleware('permission:program.eligibility.view');
        Route::get('/{id}/eligible-mustahiks', [ProgramController::class, 'eligible'])->middleware('permission:program.eligibility.view');
        Route::get('/{id}/enrollments', [ProgramController::class, 'enrollments'])->middleware('permission:program.enrollment.view');
        Route::post('/{id}/enrollments', [ProgramController::class, 'enroll'])->middleware('permission:program.enrollment.create');
        Route::get('/{id}/waitlist', [ProgramController::class, 'waitlist'])->middleware('permission:program.enrollment.view');
        Route::post('/{id}/waitlist', [ProgramController::class, 'addWaitlist'])->middleware('permission:program.enrollment.create');
        Route::get('/{id}/activities', [ProgramController::class, 'activities'])->middleware('permission:program.activity.view');
        Route::post('/{id}/activities', [ProgramController::class, 'storeActivity'])->middleware('permission:program.activity.create');
        Route::get('/{id}/targets', [ProgramController::class, 'targets'])->middleware('permission:program.target.view');
        Route::post('/{id}/targets', [ProgramController::class, 'storeTarget'])->middleware('permission:program.target.manage');
        Route::post('/{id}/outputs', [ProgramController::class, 'storeOutput'])->middleware('permission:program.output.manage');
        Route::post('/{id}/outcomes', [ProgramController::class, 'storeOutcome'])->middleware('permission:program.outcome.manage');
        Route::get('/{id}', [ProgramController::class, 'show'])->middleware('permission:program.view');
        Route::patch('/{id}', [ProgramController::class, 'update'])->middleware('permission:program.update');
        Route::post('/{id}/submit', fn (string $id, ProgramController $controller) => $controller->transition($id, 'pending_approval'))->middleware('permission:program.submit');
        Route::post('/{id}/approve', fn (string $id, ProgramController $controller) => $controller->transition($id, 'active'))->middleware('permission:program.approve');
        Route::post('/{id}/suspend', fn (string $id, ProgramController $controller) => $controller->transition($id, 'suspended'))->middleware('permission:program.suspend');
        Route::post('/{id}/activate', fn (string $id, ProgramController $controller) => $controller->transition($id, 'active'))->middleware('permission:program.activate');
        Route::post('/{id}/complete', fn (string $id, ProgramController $controller) => $controller->transition($id, 'completed'))->middleware('permission:program.complete');
        Route::post('/{id}/close', fn (string $id, ProgramController $controller) => $controller->transition($id, 'closed'))->middleware('permission:program.close');
        Route::post('/{id}/cancel', fn (string $id, ProgramController $controller) => $controller->transition($id, 'cancelled'))->middleware('permission:program.cancel');
        Route::post('/{id}/archive', fn (string $id, ProgramController $controller) => $controller->transition($id, 'archived'))->middleware('permission:program.archive');
    });
    Route::post('/program-enrollments/{id}/approve', [ProgramController::class, 'approveEnrollment'])->middleware('permission:program.enrollment.approve');
    Route::post('/program-enrollments/{id}/reject', [ProgramController::class, 'rejectEnrollment'])->middleware('permission:program.enrollment.reject');
    Route::post('/program-enrollments/{id}/withdraw', [ProgramController::class, 'withdrawEnrollment'])->middleware('permission:program.enrollment.withdraw');
    Route::patch('/program-budgets/{id}', [ProgramController::class, 'updateBudget'])->middleware('permission:program.budget.update');
    Route::post('/program-budgets/{id}/approve', [ProgramController::class, 'approveBudget'])->middleware('permission:program.budget.approve');
    Route::post('/program-eligibility-evaluations/{id}/override', [ProgramController::class, 'overrideEligibility'])->middleware('permission:program.eligibility.manage');
    Route::patch('/program-activities/{id}', [ProgramController::class, 'updateActivity'])->middleware('permission:program.activity.update');
    Route::post('/program-activities/{id}/participants', [ProgramController::class, 'addParticipant'])->middleware('permission:program.activity.manage');
    Route::patch('/program-targets/{id}', [ProgramController::class, 'updateTarget'])->middleware('permission:program.target.manage');
    Route::patch('/program-outputs/{id}', [ProgramController::class, 'updateOutput'])->middleware('permission:program.output.manage');
    Route::patch('/program-outcomes/{id}', [ProgramController::class, 'updateOutcome'])->middleware('permission:program.outcome.manage');
    Route::post('/programs/{id}/commitments', [ProgramController::class, 'commitment'])->middleware('permission:program.budget.create');
    Route::post('/program-commitments/{id}/disburse', [ProgramController::class, 'disburseCommitment'])->middleware('permission:program.budget.update');
    Route::prefix('distributions')->middleware('throttle:financial')->group(function () {
        Route::get('/', [DistributionController::class, 'index'])->middleware('permission:distribution.view');
        Route::get('/summary', [DistributionController::class, 'summary'])->middleware('permission:distribution.view');
        Route::post('/', [DistributionController::class, 'store'])->middleware('permission:distribution.create');
        Route::get('/{id}', [DistributionController::class, 'show'])->middleware('permission:distribution.view');
        Route::patch('/{id}', [DistributionController::class, 'update'])->middleware('permission:distribution.update');

        Route::post('/{id}/submit', [DistributionController::class, 'submit'])->middleware('permission:distribution.submit');
        Route::post('/{id}/approve', [DistributionController::class, 'approve'])->middleware('permission:distribution.approve');
        Route::post('/{id}/reject', [DistributionController::class, 'reject'])->middleware('permission:distribution.reject');
        Route::post('/{id}/reserve', [DistributionController::class, 'reserve'])->middleware('permission:distribution.reserve');
        Route::post('/{id}/schedule', [DistributionController::class, 'schedule'])->middleware('permission:distribution.schedule');
        Route::post('/{id}/process', [DistributionController::class, 'process'])->middleware('permission:distribution.process');
        Route::post('/{id}/fail', [DistributionController::class, 'fail'])->middleware('permission:distribution.process');
        Route::post('/{id}/complete', [DistributionController::class, 'complete'])->middleware('permission:distribution.complete');
        Route::post('/{id}/cancel', [DistributionController::class, 'cancel'])->middleware('permission:distribution.cancel');
        Route::post('/{id}/reverse', [DistributionController::class, 'reverse'])->middleware('permission:distribution.reverse');

        Route::get('/{id}/proofs', [DistributionController::class, 'proofs'])->middleware('permission:distribution.proof.view');
        Route::post('/{id}/proofs', [DistributionController::class, 'storeProof'])->middleware('permission:distribution.proof.upload');
        Route::post('/{id}/proofs/{proofId}/verify', [DistributionController::class, 'verifyProof'])->middleware('permission:distribution.proof.verify');
        Route::post('/{id}/confirm', [DistributionController::class, 'confirm'])->middleware('permission:distribution.confirm');
    });
    Route::prefix('distribution-batches')->middleware('throttle:financial')->group(function () {
        Route::get('/', [DistributionBatchController::class, 'index'])->middleware('permission:distribution.batch.view');
        Route::post('/', [DistributionBatchController::class, 'store'])->middleware('permission:distribution.batch.create');
        Route::get('/{id}', [DistributionBatchController::class, 'show'])->middleware('permission:distribution.batch.view');
        Route::post('/{id}/beneficiaries', [DistributionBatchController::class, 'storeBeneficiary'])->middleware('permission:distribution.batch.update');
        Route::delete('/{id}/beneficiaries/{beneficiaryId}', [DistributionBatchController::class, 'destroyBeneficiary'])->middleware('permission:distribution.batch.update');
        Route::post('/{id}/validate', [DistributionBatchController::class, 'validateBatch'])->middleware('permission:distribution.batch.update');
        Route::post('/{id}/submit', [DistributionBatchController::class, 'submit'])->middleware('permission:distribution.batch.update');
        Route::post('/{id}/approve', [DistributionBatchController::class, 'approve'])->middleware('permission:distribution.batch.approve');
        Route::post('/{id}/process', [DistributionBatchController::class, 'process'])->middleware('permission:distribution.batch.process');
        Route::post('/{id}/cancel', [DistributionBatchController::class, 'cancel'])->middleware('permission:distribution.batch.update');
    });
    Route::prefix('distribution-requests')->group(function () {
        Route::get('/', [DistributionController::class, 'requests'])->middleware('permission:distribution.request.view');
        Route::post('/', [DistributionController::class, 'storeRequest'])->middleware('permission:distribution.request.create');
        Route::get('/{id}', [DistributionController::class, 'showRequest'])->middleware('permission:distribution.request.view');
        Route::post('/{id}/approve', [DistributionController::class, 'approveRequest'])->middleware('permission:distribution.request.approve');
        Route::post('/{id}/reject', [DistributionController::class, 'rejectRequest'])->middleware('permission:distribution.request.reject');
    });
});
