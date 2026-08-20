# PRD MODULE 22 — SECURITY & APPLICATION SECURITY

Project: ZETRA
Module: Security
Module Code: SEC
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 17-audit-trail.md
- 21-coding-standard.md

Related Modules:

- All Modules

---

# PRD 22A — OVERVIEW

## 1. Purpose

Modul dan standar Security bertujuan untuk melindungi ZETRA dari:

- Unauthorized Access.
- IDOR.
- BOLA.
- Privilege Escalation.
- Parameter Tampering.
- Mass Assignment.
- Double Transaction.
- Replay Attack.
- Duplicate Request.
- Race Condition.
- CSRF.
- XSS.
- SQL Injection.
- File Upload Abuse.
- Webhook Forgery.
- Data Leakage.
- Cross Organization Data Access.
- Brute Force.
- Enumeration Attack.
- Sensitive Data Exposure.

Security harus diterapkan sebagai:

Defense in Depth.

Tidak boleh bergantung hanya pada:

- Frontend validation.
- Hidden button.
- Hidden menu.
- Disabled input.
- Client-side permission check.

Frontend hanya merupakan:

User Experience Layer.

Backend adalah:

Security Enforcement Layer.

---

# PRD 22B — ZERO TRUST PRINCIPLE

## 2. Never Trust Client Input

Backend tidak boleh mempercayai:

- ID dari frontend.
- Organization ID dari frontend.
- User ID dari frontend.
- Role dari frontend.
- Permission dari frontend.
- Amount dari frontend tanpa validasi.
- Status dari frontend.
- Price atau calculation dari frontend.
- Approval state dari frontend.
- Hidden field.
- Disabled field.
- Request parameter yang dianggap aman.

Semua input dari client dianggap:

Untrusted.

---

## 3. Backend Source of Truth

Untuk data sensitif:

Backend harus menentukan sendiri:

Authenticated User.

Organization Scope.

User Permission.

Current State.

Allowed Transition.

Current Balance.

Calculated Amount.

Transaction Status.

Ownership.

Authority.

---

# PRD 22C — BACKEND VALIDATION

## 4. Validation Principle

Frontend validation digunakan untuk:

- User Experience.
- Early Feedback.
- Required Field Indicator.
- Format Validation sederhana.

Backend validation digunakan untuk:

- Security.
- Data Integrity.
- Business Rule Enforcement.

Semua validasi penting WAJIB dilakukan kembali di backend.

---

## 5. Sensitive Field Validation

Field berikut tidak boleh dipercaya dari frontend:

organization_id

user_id

approved_by

created_by

updated_by

status

balance

available_balance

ledger_balance

fund_balance

total_amount

approved_amount

distributed_amount

payment_status

transaction_status

reference_number

permission

role

is_admin.

Backend harus:

Mengabaikan input tersebut.

Atau:

Memvalidasi secara ketat berdasarkan business rule.

---

## 6. Form Request

Semua endpoint yang menerima input utama wajib menggunakan:

Laravel Form Request.

Contoh:

StoreCollectionRequest.

UpdateCollectionRequest.

ApproveDistributionRequest.

ConfirmPaymentRequest.

CreateProgramRequest.

UpdateFundRequest.

Validation tidak boleh hanya berada di:

React Form.

JavaScript.

Frontend Schema.

---

# PRD 22D — AUTHENTICATION

## 7. Authentication Principle

Semua endpoint yang membutuhkan authentication wajib:

Memverifikasi authenticated user.

Tidak boleh mempercayai:

user_id dari request.

Contoh buruk:

POST /distribution

{
    "user_id": 999
}

Backend tidak boleh menggunakan:

$request->user_id

untuk menentukan pelaku.

Gunakan:

$request->user()

atau:

auth()->user().

---

# PRD 22E — AUTHORIZATION

## 8. Authorization Is Mandatory

Authentication tidak berarti Authorization.

User yang berhasil login belum tentu boleh:

- melihat data;
- mengubah data;
- menghapus data;
- menyetujui transaksi;
- melihat organisasi lain.

Setiap sensitive action harus memiliki authorization.

---

## 9. Authorization Layer

Gunakan kombinasi:

Route Middleware.

Permission.

Policy.

Service Level Business Authorization.

---

# PRD 22F — IDOR / BOLA PROTECTION

## 10. Object Level Authorization

Setiap endpoint yang menerima:

ID.

UUID.

Reference Number.

Slug.

Code.

Identifier lainnya.

WAJIB memvalidasi apakah authenticated user memiliki akses terhadap object tersebut.

Contoh:

GET

/api/v1/distributions/{id}

Tidak cukup:

Distribution::findOrFail($id)

Harus memastikan:

User memiliki hak akses terhadap Distribution tersebut.

---

## 11. IDOR Example

User A:

GET

/api/v1/mustahik/100

User kemudian mengubah URL menjadi:

GET

/api/v1/mustahik/101

Jika ID 101 milik Organization lain:

Backend harus menolak.

Tidak boleh hanya mengandalkan:

Frontend tidak menampilkan tombol.

---

## 12. Organization Scope Protection

Semua query organization-scoped harus dibatasi.

Contoh konsep:

Mustahik::query()
    ->where('organization_id', $currentOrganizationId)

    ->whereKey($id)

    ->firstOrFail();

Jangan:

Mustahik::findOrFail($id);

kemudian baru berharap frontend sudah membatasi akses.

---

## 13. Policy Requirement

Gunakan Laravel Policy untuk entity sensitif.

Contoh:

MuzakkiPolicy.

MustahikPolicy.

CollectionPolicy.

DistributionPolicy.

FundPolicy.

ProgramPolicy.

DocumentPolicy.

ReportPolicy.

---

# PRD 22G — ORGANIZATION ISOLATION

## 14. Multi Organization Security

Organization ID dari request tidak boleh menjadi source of truth.

Contoh buruk:

POST /collection

{
    "organization_id": 99
}

Backend harus menentukan organization berdasarkan:

- User Context.
- Selected Organization yang tervalidasi.
- Membership.
- Explicit Authorization.

---

## 15. Cross Organization Access

Cross Organization Access hanya diperbolehkan apabila:

User memiliki explicit permission.

Contoh:

organization.cross_access.

Atau permission khusus sesuai arsitektur.

Tidak boleh terjadi:

User mengganti organization_id di request.

Lalu mendapatkan akses ke data organisasi lain.

---

# PRD 22H — MASS ASSIGNMENT

## 16. Mass Assignment Protection

Jangan menggunakan:

Model::create($request->all())

atau:

$model->update($request->all())

Gunakan:

$request->validated()

Kemudian hanya gunakan field yang memang diperbolehkan.

Contoh:

$validated = $request->validated();

$entity->update([
    'name' => $validated['name'],
    'phone' => $validated['phone'],
]);

Field sensitif tidak boleh dapat dimodifikasi secara tidak sengaja.

---

# PRD 22I — IDEMPOTENCY

## 17. Idempotency Principle

Endpoint transaksi sensitif wajib mempertimbangkan Idempotency.

Tujuan:

Request yang sama tidak boleh menghasilkan transaksi ganda.

Contoh risiko:

User klik tombol:

Pay

dua kali.

Atau:

Browser melakukan retry.

Atau:

Network timeout.

User tidak tahu transaksi berhasil.

Kemudian mengirim request kembali.

---

## 18. Sensitive Idempotent Operations

Minimal:

Payment Creation.

Payment Confirmation.

Payment Webhook Processing.

Collection Recording.

Distribution Execution.

Fund Transfer.

Ledger Posting.

Refund.

Reversal.

Bank Transaction Import.

Scheduled Financial Job.

External Callback.

---

## 19. Idempotency Key

Client dapat mengirim:

Idempotency-Key.

Contoh:

Idempotency-Key: 550e8400e29b41d4a716446655440000

Backend menyimpan key tersebut.

Entity:

idempotency_keys.

Fields:

id

organization_id

idempotency_key

request_hash

endpoint

resource_type

resource_id

status

response_code

response_body

expires_at

created_at

updated_at.

---

## 20. Idempotency Flow

Request masuk.

↓

Validate Authentication.

↓

Validate Authorization.

↓

Read Idempotency Key.

↓

Check Existing Key.

Jika belum ada:

↓

Create Processing Record.

↓

Execute Transaction.

↓

Store Response.

↓

Return Response.

Jika key sudah ada dan request sama:

↓

Return Previous Response.

Jika key sama tetapi request berbeda:

↓

Reject Request.

Status:

409 Conflict.

---

## 21. Idempotency Rules

Idempotency Key harus:

- unique sesuai scope;
- memiliki expiration;
- terkait endpoint;
- terkait organization;
- terkait request payload hash jika diperlukan.

Tidak boleh:

Menggunakan Idempotency Key global tanpa scope.

---

# PRD 22J — DUPLICATE TRANSACTION PROTECTION

## 22. Duplicate Protection

Selain Idempotency Key, sistem harus memiliki:

Business-Level Duplicate Protection.

Contoh:

Payment Reference.

External Transaction ID.

Bank Transaction Reference.

Gateway Transaction ID.

Receipt Number.

Reference Number.

Field identifier tersebut harus memiliki:

Unique Constraint.

---

## 23. Database Is Final Protection

Validation di application tidak cukup.

Untuk data yang wajib unik:

Gunakan:

Database Unique Index.

Contoh:

gateway_transaction_id

harus UNIQUE jika memang secara domain bersifat unik.

---

# PRD 22K — RACE CONDITION

## 24. Race Condition Protection

Proses sensitif harus aman terhadap concurrent request.

Contoh:

Fund Balance:

Rp1.000.000.

Request A:

Distribution Rp800.000.

Request B:

Distribution Rp800.000.

Jika keduanya membaca balance yang sama:

Dapat terjadi overdraft.

---

## 25. Concurrency Protection

Gunakan sesuai kebutuhan:

Database Transaction.

Row Lock.

Optimistic Lock.

Unique Constraint.

Atomic Update.

Queue Serialization.

---

## 26. Financial Locking

Untuk operasi yang mengubah balance:

Gunakan database transaction.

Dan locking apabila diperlukan.

Contoh konsep:

DB::transaction(function () {

    $fund = Fund::query()
        ->lockForUpdate()
        ->findOrFail($fundId);

    // Validate balance

    // Execute transaction

    // Update balance

});

Jangan:

Read Balance

↓

Check Balance

↓

Update Balance

tanpa protection terhadap concurrent request.

---

# PRD 22L — STATE TRANSITION SECURITY

## 27. State Must Be Valid

Frontend tidak boleh menentukan state transition secara bebas.

Contoh:

Distribution:

DRAFT

↓

SUBMITTED

↓

APPROVED

↓

EXECUTED.

Tidak boleh:

DRAFT

langsung menjadi:

EXECUTED.

hanya karena request mengirim:

{
    "status": "EXECUTED"
}

---

## 28. Backend State Machine

State transition harus divalidasi oleh backend.

Contoh:

approve()

hanya dapat dilakukan jika:

Current Status:

SUBMITTED.

Jika status:

DRAFT

atau:

EXECUTED.

Maka request harus ditolak.

---

## 29. Sensitive State Transition

Transition berikut membutuhkan:

Authorization.

Current State Validation.

Business Rule Validation.

Audit Log.

Contoh:

Approve.

Reject.

Execute.

Cancel.

Reverse.

Refund.

Close Period.

Reopen Period.

---

# PRD 22M — FINANCIAL SECURITY

## 30. Financial Amount

Frontend tidak boleh menjadi source of truth untuk:

Balance.

Final Allocation.

Available Fund.

Remaining Fund.

Ledger Balance.

Approval Amount.

Final Distribution Amount.

Backend wajib:

Mengambil kondisi terbaru dari database.

Melakukan calculation.

Melakukan validation.

---

## 31. Decimal and Money

Tidak menggunakan:

float

untuk nilai uang.

Gunakan:

integer smallest currency unit.

Atau:

decimal dengan precision yang ditentukan secara konsisten.

Standar final harus digunakan secara global.

---

## 32. Ledger Integrity

Ledger yang telah posted tidak boleh:

Diubah langsung.

Koreksi dilakukan melalui:

Adjustment.

Reversal.

Corrective Entry.

Setiap perubahan harus:

Traceable.

Auditable.

---

# PRD 22N — REPLAY ATTACK PROTECTION

## 33. Replay Protection

Request sensitif tidak boleh dapat digunakan ulang secara bebas.

Gunakan kombinasi:

Idempotency Key.

Timestamp.

Nonce jika diperlukan.

Signature untuk webhook.

Expiration Window.

---

# PRD 22O — WEBHOOK SECURITY

## 34. Webhook Verification

Webhook tidak boleh dipercaya hanya berdasarkan:

IP.

Header biasa.

Payload.

Minimal harus:

Verify Signature.

Verify Timestamp jika tersedia.

Validate Event ID.

Validate Provider.

Apply Idempotency.

---

## 35. Webhook Flow

Webhook Received.

↓

Verify Signature.

↓

Validate Timestamp.

↓

Check Event ID.

↓

Check Duplicate.

↓

Store Event.

↓

Queue Processing.

↓

Process.

↓

Update Status.

↓

Audit Event.

---

## 36. Webhook Event

Entity:

webhook_events.

Fields:

id

provider

event_id

event_type

signature_valid

payload

status

processed_at

created_at.

event_id harus unique sesuai provider jika provider menjamin uniqueness.

---

# PRD 22P — RATE LIMITING

## 37. Rate Limit

Gunakan Rate Limiting untuk endpoint:

Login.

Password Reset.

OTP.

Payment.

Public API.

Webhook sesuai kebutuhan.

Report Export.

Search berat.

---

## 38. Rate Limit Rules

Rate limit harus mempertimbangkan:

User.

IP.

Organization.

Endpoint.

Tidak hanya IP.

Karena:

Satu organization dapat memiliki banyak user.

---

# PRD 22Q — INPUT SECURITY

## 39. SQL Injection

Gunakan:

Eloquent.

Query Builder.

Binding.

Jangan melakukan:

Raw SQL dengan input langsung.

---

## 40. XSS

Semua input user harus dianggap:

Potentially Unsafe.

Output harus:

Escaped.

Rich Text harus:

Sanitized.

Tidak boleh:

Menampilkan HTML dari user secara langsung tanpa sanitization.

---

# PRD 22R — FILE UPLOAD SECURITY

## 41. File Validation

Upload harus memvalidasi:

- File Type.
- MIME Type.
- File Size.
- Extension.
- File Content jika memungkinkan.

Tidak cukup:

Memeriksa extension.

---

## 42. File Storage

File sensitif tidak boleh otomatis berada pada:

Public URL.

Gunakan:

Private Storage.

Signed URL.

Temporary URL.

Authorization Check.

---

## 43. Dangerous File

File executable tidak boleh diterima.

Contoh:

PHP.

EXE.

SH.

BAT.

JS executable dalam konteks berbahaya.

File upload harus mengikuti whitelist.

---

# PRD 22S — SENSITIVE DATA

## 44. Sensitive Data Protection

Data sensitif harus:

- tidak muncul di response jika tidak diperlukan;
- tidak masuk log secara sembarangan;
- tidak muncul pada error message;
- tidak dapat diakses lintas organisasi;
- mengikuti permission.

---

## 45. Sensitive Logging

Jangan mencatat secara plain:

Password.

Access Token.

Refresh Token.

Secret.

Payment Credential.

Private Key.

Webhook Secret.

Sensitive Personal Data yang tidak diperlukan.

---

# PRD 22T — SECRETS MANAGEMENT

## 46. Secret Rule

Secret tidak boleh:

Hardcoded dalam source code.

Commit ke repository.

Masuk ke frontend build.

Masuk ke public configuration.

Gunakan:

Environment Configuration.

Secret Management sesuai deployment environment.

---

# PRD 22U — ERROR SECURITY

## 47. Production Error

Production tidak boleh menampilkan:

Stack Trace.

SQL Error.

Internal Path.

Secret.

Configuration.

Internal Exception Detail.

User menerima:

Safe Error Message.

Detail error disimpan pada:

Secure Log.

---

# PRD 22V — AUDIT AND SECURITY EVENT

## 48. Security Event

Minimal event:

authentication.failed

authentication.locked

authorization.denied

idor.attempt

organization.access_denied

permission.denied

payment.duplicate_attempt

payment.idempotency_conflict

webhook.signature_invalid

webhook.replay_detected

rate_limit.exceeded

sensitive_action.performed

data.exported

document.downloaded.

---

## 49. Security Audit

Security Event harus:

- memiliki timestamp;
- actor jika diketahui;
- organization context;
- resource context;
- request metadata yang aman;
- correlation ID jika tersedia.

---

# PRD 22W — SECURITY HEADERS

## 50. HTTP Security

Production harus mempertimbangkan:

HTTPS.

HSTS.

Content Security Policy.

X-Content-Type-Options.

Clickjacking Protection.

Referrer Policy.

Secure Cookie.

HttpOnly Cookie.

SameSite Cookie.

Konfigurasi harus disesuaikan dengan arsitektur frontend dan authentication.

---

# PRD 22X — API SECURITY

## 51. API Rules

API harus:

- authenticated jika private;
- authorized;
- rate limited;
- validated;
- scoped;
- paginated untuk list;
- tidak mengekspos internal field.

---

## 52. API Object Access

Endpoint berikut harus diperiksa secara khusus:

GET /resource/{id}

PATCH /resource/{id}

DELETE /resource/{id}

POST /resource/{id}/approve

POST /resource/{id}/reject

POST /resource/{id}/execute

POST /resource/{id}/cancel

Semua harus memiliki:

Authentication.

Authorization.

Object Ownership / Scope Validation.

State Validation.

---

# PRD 22Y — SECURITY TESTING

## 53. Mandatory Security Test

Minimal test:

- Unauthorized Access.
- Unauthorized Object Access.
- IDOR.
- Cross Organization Access.
- Privilege Escalation.
- Parameter Tampering.
- Mass Assignment.
- Duplicate Request.
- Idempotency.
- Race Condition untuk financial flow.
- Invalid State Transition.
- Webhook Invalid Signature.
- Webhook Duplicate Event.
- Rate Limit.
- Sensitive Field Manipulation.
- File Upload Validation.
- Sensitive Data Exposure.

---

## 54. IDOR Test Example

User A membuat:

Distribution A.

User B mencoba:

GET Distribution A.

Expected:

403 Forbidden

atau:

404 sesuai security strategy.

Tidak boleh:

200 OK.

---

## 55. Sensitive Field Tampering Test

Client mengirim:

{
    "status": "APPROVED",
    "approved_by": 999,
    "organization_id": 999
}

Backend harus:

Mengabaikan field tersebut.

Atau:

Reject Request.

Tidak boleh:

Menerima state atau ownership dari client tanpa validasi.

---

## 56. Idempotency Test

Request:

POST /payments

Idempotency-Key:

ABC123.

Request berhasil.

Request kedua dengan:

Key sama.

Payload sama.

Expected:

Tidak membuat payment baru.

Return:

Existing Result.

---

## 57. Idempotency Conflict Test

Key:

ABC123.

Payload pertama:

Amount 100000.

Payload kedua:

Amount 200000.

Expected:

409 Conflict.

Tidak boleh:

Memproses transaksi kedua.

---

## 58. Race Condition Test

Dua request concurrent mencoba:

Menggunakan fund yang sama.

Expected:

Hanya transaksi yang valid yang berhasil.

Balance tidak boleh:

Negative.

Double spent.

---

# PRD 22Z — SECURITY ACCEPTANCE CRITERIA

- [ ] Backend validation diterapkan.
- [ ] Frontend bukan source of truth.
- [ ] Sensitive field tidak dipercaya dari client.
- [ ] Authentication diterapkan.
- [ ] Authorization diterapkan.
- [ ] Object Level Authorization diterapkan.
- [ ] IDOR protection tersedia.
- [ ] BOLA protection tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Cross Organization Access dibatasi.
- [ ] Mass Assignment dilindungi.
- [ ] Sensitive transaction menggunakan Idempotency.
- [ ] Duplicate transaction dicegah.
- [ ] Database Unique Constraint digunakan.
- [ ] Race Condition diperhitungkan.
- [ ] Financial operation menggunakan Transaction.
- [ ] Locking digunakan jika diperlukan.
- [ ] State Transition tervalidasi.
- [ ] Amount dihitung dan divalidasi di backend.
- [ ] Webhook Signature diverifikasi.
- [ ] Webhook Duplicate dicegah.
- [ ] Replay Protection diterapkan.
- [ ] Rate Limiting diterapkan.
- [ ] SQL Injection dicegah.
- [ ] XSS dicegah.
- [ ] File Upload aman.
- [ ] Sensitive File menggunakan private storage.
- [ ] Secret tidak hardcoded.
- [ ] Sensitive Data tidak masuk log.
- [ ] Production Error aman.
- [ ] Security Event dicatat.
- [ ] Security Test tersedia.

---

# DEFINITION OF DONE

Security dianggap memenuhi standar apabila:

1. Tidak ada sensitive action yang hanya divalidasi di frontend.
2. Backend menjadi source of truth.
3. Setiap object sensitif memiliki authorization.
4. IDOR dan BOLA telah diuji.
5. Organization isolation diterapkan pada seluruh query.
6. organization_id dari client tidak dipercaya secara langsung.
7. user_id dari client tidak digunakan untuk menentukan authenticated actor.
8. Mass Assignment dilindungi.
9. Sensitive transaction mendukung Idempotency.
10. Duplicate transaction tidak dapat terjadi.
11. Database menjadi lapisan proteksi terakhir untuk uniqueness.
12. Financial process aman terhadap race condition.
13. Critical operation menggunakan database transaction.
14. Locking digunakan pada operasi concurrent yang membutuhkan.
15. State transition tidak dapat dimanipulasi dari frontend.
16. Financial amount divalidasi dan dihitung di backend.
17. Webhook diverifikasi menggunakan signature.
18. Webhook replay dan duplicate event ditangani.
19. Rate limiting diterapkan.
20. File upload menggunakan whitelist dan private storage jika sensitif.
21. Secret tidak ada di source code.
22. Sensitive data tidak bocor melalui API atau log.
23. Production error tidak mengekspos internal system.
24. Security event tercatat.
25. Automated security test tersedia.
26. Security checklist menjadi bagian wajib Pull Request Review.

---

# SECURITY GOLDEN RULES

1. Never trust the frontend.
2. Authentication is not authorization.
3. Every object access must be authorized.
4. Every sensitive transaction must be idempotent or duplicate-safe.
5. Every financial operation must be concurrency-safe.
6. Database constraints are part of security.
7. Backend determines truth.
8. Sensitive state transitions are server-controlled.
9. Organization boundaries must never be bypassable by changing IDs.
10. Security must be tested, not assumed.

---

# END OF PRD MODULE 22 — SECURITY & APPLICATION SECURITY