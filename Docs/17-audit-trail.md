# PRD MODULE 17 — AUDIT TRAIL

Project: ZETRA
Module: Audit Trail
Module Code: AUD
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md

Related Modules:

- All Modules

---

# PRD 17A — OVERVIEW

## 1. Purpose

Modul Audit Trail bertanggung jawab untuk mencatat aktivitas penting yang terjadi di dalam ZETRA.

Audit Trail menjadi centralized audit system.

Semua module dapat mengirim Audit Event.

Contoh:

User melakukan perubahan data Mustahik

↓

Module Event

↓

Audit Trail

↓

Store Audit Record

↓

Immutable History.

Tujuan utama:

- Accountability.
- Transparency.
- Traceability.
- Security Investigation.
- Compliance.
- Fraud Detection Support.
- Data Change Tracking.

---

## 2. Goals

Modul harus mampu:

1. Mencatat aktivitas user.
2. Mencatat aktivitas system.
3. Mencatat perubahan data.
4. Menyimpan before value.
5. Menyimpan after value.
6. Mencatat siapa yang melakukan perubahan.
7. Mencatat waktu aktivitas.
8. Mencatat sumber aktivitas.
9. Mencatat IP Address apabila tersedia.
10. Mencatat User Agent apabila tersedia.
11. Mendukung audit berdasarkan Organization.
12. Mendukung audit berdasarkan Module.
13. Mendukung audit berdasarkan Entity.
14. Mendukung audit berdasarkan User.
15. Mendukung pencarian dan filtering.
16. Mendukung detail perubahan.
17. Mendukung export audit.
18. Mendukung immutable audit record.
19. Mendukung retention policy.
20. Mendukung system generated audit event.

---

# PRD 17B — CORE PRINCIPLE

## 3. Audit Record is Immutable

Audit record yang telah dibuat tidak boleh dapat diedit.

Tidak boleh:

UPDATE audit record.

Tidak boleh:

DELETE audit record secara langsung.

Jika terdapat kesalahan sistem:

Buat audit event baru.

Jangan mengubah audit sebelumnya.

---

## 4. Append Only Principle

Audit Trail menggunakan prinsip:

APPEND ONLY.

Contoh:

User A mengubah status Payment.

Audit:

PAYMENT_UPDATED.

Jika status kemudian diubah lagi:

Buat record baru:

PAYMENT_UPDATED.

Audit sebelumnya tetap ada.

---

## 5. Centralized Audit

Setiap module tidak membuat tabel audit sendiri.

Gunakan:

Audit Trail Module.

Module hanya mengirim:

Audit Event.

Contoh:

Mustahik Module

↓

MUSTAHIK_UPDATED

↓

Audit Trail.

Distribution Module

↓

DISTRIBUTION_APPROVED

↓

Audit Trail.

---

# PRD 17C — AUDIT LOG ENTITY

## 6. Entity

audit_logs

Fields:

id

organization_id

audit_number

event_name

event_category

module_code

entity_type

entity_id

entity_reference

action

description

old_values

new_values

metadata

actor_type

actor_id

actor_name

ip_address

user_agent

request_id

occurred_at

created_at

---

## 7. Primary Key

Primary key menggunakan:

ULID.

Contoh:

01JABCDE123456789.

---

## 8. Audit Number

Format:

AUD{YEAR}{SEQUENCE}

Contoh:

AUD2026000001

AUD2026000002

Rules:

- unique;
- immutable;
- uppercase;
- tidak menggunakan dash;
- human readable.

---

# PRD 17D — EVENT CATEGORY

## 9. Initial Event Category

AUTHENTICATION

AUTHORIZATION

CREATE

UPDATE

DELETE

RESTORE

APPROVAL

REJECTION

PAYMENT

COLLECTION

DISTRIBUTION

ASSESSMENT

PROGRAM

DOCUMENT

BANKING

ACCOUNTING

NOTIFICATION

CONFIGURATION

SECURITY

SYSTEM

OTHER.

---

# PRD 17E — ACTION

## 10. Initial Action

LOGIN

LOGOUT

LOGIN_FAILED

CREATE

VIEW

UPDATE

DELETE

RESTORE

APPROVE

REJECT

SUBMIT

CANCEL

IMPORT

EXPORT

UPLOAD

DOWNLOAD

VERIFY

MATCH

UNMATCH

CLOSE

REOPEN

ACTIVATE

DEACTIVATE

ASSIGN

REMOVE

SEND

RETRY

OTHER.

---

# PRD 17F — ACTOR

## 11. Actor Type

USER

SYSTEM

API

JOB

WEBHOOK

OTHER.

---

## 12. User Actor

Contoh:

actor_type:

USER.

actor_id:

User ULID.

actor_name:

Nama User saat event terjadi.

Actor Name disimpan sebagai snapshot agar histori tetap terbaca apabila nama user berubah.

---

## 13. System Actor

Contoh:

actor_type:

SYSTEM.

actor_id:

NULL.

actor_name:

SYSTEM.

---

# PRD 17G — ENTITY REFERENCE

## 14. Purpose

Entity Reference digunakan agar audit mudah dibaca manusia.

Contoh:

entity_type:

PAYMENT.

entity_id:

01JXYZ.

entity_reference:

PAY2026000001.

Contoh lain:

entity_type:

MUSTAHIK.

entity_reference:

MST2026000123.

---

# PRD 17H — DATA CHANGE TRACKING

## 15. Old Values

Field:

old_values.

Menyimpan data sebelum perubahan.

Format:

JSON.

Contoh:

{
    "status": "PENDING",
    "amount": 500000
}

---

## 16. New Values

Field:

new_values.

Menyimpan data setelah perubahan.

Format:

JSON.

Contoh:

{
    "status": "APPROVED",
    "amount": 500000
}

---

## 17. Sensitive Field Protection

Tidak semua field boleh disimpan secara penuh.

Field sensitif dapat:

- di-mask;
- di-redact;
- tidak dicatat.

Contoh:

password:

[REDACTED]

account_number:

******7890.

token:

[REDACTED].

---

# PRD 17I — METADATA

## 18. Purpose

Metadata menyimpan informasi tambahan.

Field:

metadata.

Format:

JSON.

Contoh:

{
    "source": "web",
    "route": "/api/v1/payments/PAY202600001",
    "method": "PATCH"
}

---

# PRD 17J — REQUEST TRACEABILITY

## 19. Request ID

Setiap HTTP Request dapat memiliki:

request_id.

Format:

REQ{YEAR}{SEQUENCE}

atau UUID / ULID internal.

Tujuan:

Satu user action dapat ditelusuri ke beberapa audit event.

Contoh:

User melakukan:

Approve Distribution.

Sistem menghasilkan:

DISTRIBUTION_APPROVED

NOTIFICATION_CREATED

ACCOUNTING_EVENT_CREATED.

Semua dapat memiliki:

request_id yang sama.

---

# PRD 17K — AUDIT EVENT FLOW

## 20. Flow

User Action

↓

Module Business Logic

↓

Transaction Success

↓

Domain Event

↓

Audit Event

↓

Audit Trail Service

↓

Sanitize Sensitive Data

↓

Generate Audit Number

↓

Store Audit Log

↓

Immutable Record.

Audit event hanya dibuat setelah business transaction berhasil apabila event merepresentasikan perubahan data yang committed.

---

# PRD 17L — AUDIT EVENT NAMING

## 21. Event Naming Convention

Format:

module.entity.action

Contoh:

auth.user.login

auth.user.login_failed

mustahik.profile.created

mustahik.profile.updated

assessment.assessment.submitted

assessment.assessment.approved

collection.zakat.created

collection.zakat.confirmed

distribution.request.created

distribution.request.approved

distribution.distribution.completed

payment.transaction.paid

bank.transaction.matched

bank.reconciliation.completed

document.file.uploaded

document.file.verified

notification.message.sent

user.role.assigned

organization.settings.updated.

---

# PRD 17M — AUDIT SEVERITY

## 22. Severity

INFO

NOTICE

WARNING

CRITICAL.

---

## 23. Severity Example

INFO:

Normal activity.

Contoh:

User melihat data.

NOTICE:

Aktivitas penting.

Contoh:

Payment created.

WARNING:

Aktivitas yang perlu diperhatikan.

Contoh:

Multiple failed login.

CRITICAL:

Aktivitas keamanan atau finansial kritis.

Contoh:

Unauthorized access attempt.

Payment reversal.

Distribution cancelled after approval.

---

# PRD 17N — AUDIT RETENTION

## 24. Purpose

Audit log tidak boleh dihapus sembarangan.

Retention Policy dapat dikonfigurasi.

Contoh:

Minimum retention:

5 tahun.

Organization dapat mengatur retention lebih panjang.

---

## 25. Archive

Audit lama dapat:

ARCHIVED.

Audit tetap dapat dicari sesuai permission.

Storage dapat dipindahkan ke:

Archive Storage.

---

## 26. Purge

Purge hanya dapat dilakukan jika:

- Retention period selesai;
- Policy mengizinkan;
- Authorized System Process;
- Purge Event dicatat.

Purge tidak tersedia untuk normal user action.

---

# PRD 17O — AUDIT INTEGRITY

## 27. Integrity Check

Untuk meningkatkan integritas, audit record dapat memiliki:

record_hash.

Hash dibuat dari data penting audit.

Contoh:

SHA256.

Data:

previous_hash

+

audit_number

+

event_name

+

entity_id

+

occurred_at.

---

## 28. Optional Hash Chain

Versi lanjutan dapat menggunakan:

previous_hash.

Contoh:

Audit 1

Hash A.

↓

Audit 2

Previous Hash A.

Hash B.

↓

Audit 3

Previous Hash B.

Hash C.

Jika audit lama diubah:

Integrity Check dapat mendeteksi perubahan.

---

# PRD 17P — AUDIT LOG VIEW

## 29. Audit List

User dapat melihat daftar Audit Log berdasarkan permission.

Columns:

Audit Number

Occurred At

Actor

Module

Event

Entity

Action

Severity

IP Address.

---

## 30. Filters

Filter:

Organization

Module

Event Category

Event Name

Entity Type

Entity Reference

Actor

Action

Severity

Date Range

IP Address.

---

# PRD 17Q — AUDIT DETAIL

## 31. Detail

Audit Detail menampilkan:

Audit Number

Event

Category

Module

Action

Description

Actor

Entity

Occurred At

Request ID

IP Address

User Agent

Old Values

New Values

Metadata.

---

## 32. Change Comparison

Untuk UPDATE Event:

Frontend menampilkan:

Field

Old Value

New Value.

Contoh:

Status

PENDING

↓

APPROVED.

---

# PRD 17R — AUDIT TIMELINE

## 33. Entity Timeline

Setiap entity dapat menampilkan audit timeline.

Contoh:

Payment:

Created

↓

Submitted

↓

Paid

↓

Reconciled.

Data berasal dari:

Audit Trail.

Tidak membuat timeline manual sendiri.

---

# PRD 17S — AUDIT EXPORT

## 34. Export

Audit dapat diexport berdasarkan permission.

Supported initial format:

CSV

XLSX.

Future:

PDF.

---

## 35. Export Rule

Sensitive data tidak boleh otomatis diexport.

Data harus mengikuti:

Audit Visibility Policy.

Contoh:

IP Address dapat dibatasi.

Account Number tetap masked.

Credential tidak pernah diexport.

---

# PRD 17T — API SPECIFICATION

## 36. Audit Logs

GET

/api/v1/audit-logs

GET

/api/v1/audit-logs/{id}

GET

/api/v1/audit-logs/entity/{entityType}/{entityId}

GET

/api/v1/audit-logs/request/{requestId}

---

## 37. Audit Export

POST

/api/v1/audit-logs/export

GET

/api/v1/audit-logs/export/{id}.

---

## 38. Integrity

POST

/api/v1/audit-logs/integrity-check

GET

/api/v1/audit-logs/integrity-check/{id}.

Versi awal endpoint integrity check dapat dibatasi hanya untuk System Administrator.

---

# PRD 17U — PERMISSIONS

## 39. Permission Codes

audit.view

audit.view_detail

audit.view_sensitive

audit.export

audit.integrity_check

audit.archive.view

audit.retention.manage

audit.system.view

audit.security.view.

---

# PRD 17V — UI REQUIREMENTS

## 40. Audit Dashboard

Cards:

Total Events Today

Critical Events

Warning Events

Failed Login

Data Changes

Financial Events.

---

## 41. Audit Log List

Gunakan ZETRA DataTable.

Columns:

Occurred At

Audit Number

Actor

Module

Event

Entity

Action

Severity.

Actions:

View Detail.

Export sesuai permission.

---

## 42. Audit Detail Page

Header:

Audit Number

Event Name

Severity

Occurred At.

Sections:

Actor Information

Entity Information

Action Detail

Changes

Request Information

Metadata

Integrity.

---

## 43. Change Viewer

Jika terdapat:

old_values

dan:

new_values.

Tampilkan:

Changed Fields Only.

Contoh:

| Field | Old Value | New Value |
| --- | --- | --- |
| status | PENDING | APPROVED |
| amount | 500000 | 750000 |

Sensitive field:

[REDACTED].

---

## 44. Entity Audit Tab

Module lain dapat menampilkan:

Audit History.

Contoh:

Mustahik Detail

Tabs:

Overview

Assessment

Distribution

Documents

Audit History.

Data diambil dari:

Audit Trail Module.

---

# PRD 17W — BUSINESS RULES

## 45. General Rules

1. Audit Record bersifat immutable.
2. Audit Record menggunakan append only principle.
3. Audit Record tidak dapat diedit.
4. Normal user tidak dapat menghapus Audit Record.
5. Setiap Audit Record harus memiliki event.
6. Setiap Audit Record harus memiliki occurred_at.
7. Actor harus dicatat apabila tersedia.
8. System Event menggunakan actor SYSTEM.
9. Organization isolation wajib diterapkan.
10. Sensitive data tidak boleh dicatat secara penuh.
11. Password tidak boleh dicatat.
12. Token tidak boleh dicatat.
13. Secret tidak boleh dicatat.
14. Credential tidak boleh dicatat.
15. Account Number harus dimasked.
16. Data change menyimpan Old Value dan New Value apabila relevan.
17. Audit event hanya dicatat setelah transaction committed untuk perubahan data penting.
18. Failed action dapat dicatat sebagai Security Event.
19. Request ID digunakan untuk traceability.
20. Audit Log dapat digunakan oleh semua module.
21. Audit Log harus dapat dicari.
22. Export harus mengikuti permission.
23. Sensitive Audit Data membutuhkan permission tambahan.
24. Retention Policy tidak boleh menghapus audit secara sembarangan.
25. Archive tidak boleh mengubah isi audit.
26. Purge hanya dilakukan melalui authorized process.
27. Integrity Check tidak boleh mengubah Audit Record.
28. Semua aktivitas pada sistem audit sendiri juga harus diaudit apabila memungkinkan.

---

# PRD 17X — AUDIT EVENTS

## 46. Core Audit Events

Authentication:

auth.user.login

auth.user.logout

auth.user.login_failed

auth.user.password_changed

auth.user.password_reset_requested

auth.user.password_reset_completed.

Authorization:

auth.role.assigned

auth.role.removed

auth.permission.updated

auth.access_denied.

Data:

entity.created

entity.updated

entity.deleted

entity.restored.

Financial:

collection.created

collection.confirmed

payment.created

payment.paid

payment.failed

distribution.created

distribution.approved

distribution.completed

distribution.cancelled

fund.transaction.created

fund.transaction.adjusted

ledger.journal.created

ledger.journal.posted

bank.transaction.matched

bank.reconciliation.completed.

Document:

document.uploaded

document.downloaded

document.deleted

document.restored

document.verified

document.rejected.

System:

system.configuration.updated

system.job.failed

system.integration.failed.

Security:

security.suspicious_activity

security.rate_limit_triggered

security.unauthorized_access_attempt.

---

# PRD 17Y — TESTING REQUIREMENTS

## 47. Unit Test

Minimal:

- Audit Number Generation.
- Event Creation.
- Actor Resolution.
- Organization Resolution.
- Old Value Storage.
- New Value Storage.
- Sensitive Data Redaction.
- Password Redaction.
- Token Redaction.
- Account Number Masking.
- Request ID Assignment.
- Event Naming Validation.
- Severity Assignment.
- Immutable Record Rule.
- Entity Timeline.
- Audit Filtering.
- Audit Export.
- Permission Validation.
- Integrity Hash Generation.

---

## 48. Integration Test

Flow:

User updates Mustahik.

↓

Database Transaction.

↓

Transaction Success.

↓

Domain Event.

↓

Audit Event.

↓

Sensitive Data Sanitization.

↓

Audit Log Created.

↓

Audit Detail Available.

↓

Entity Timeline Updated.

---

## 49. Security Test

Test:

- Unauthorized Audit Access.
- Cross Organization Audit Access.
- Audit Record Modification.
- Audit Record Deletion.
- Sensitive Data Exposure.
- Password Exposure.
- Token Exposure.
- Credential Exposure.
- Unauthorized Export.
- Audit Integrity Manipulation.
- Request ID Spoofing.
- Audit Bypass Attempt.

---

# PRD 17Z — ACCEPTANCE CRITERIA

- [ ] Audit Log dapat dibuat.
- [ ] Audit Number otomatis dibuat.
- [ ] Audit bersifat immutable.
- [ ] Append only diterapkan.
- [ ] Actor dicatat.
- [ ] System Actor didukung.
- [ ] Organization dicatat.
- [ ] Module dicatat.
- [ ] Entity dicatat.
- [ ] Entity Reference tersedia.
- [ ] Action dicatat.
- [ ] Event Category tersedia.
- [ ] Old Value tersedia.
- [ ] New Value tersedia.
- [ ] Sensitive Data di-redact.
- [ ] Password tidak tercatat.
- [ ] Token tidak tercatat.
- [ ] Request ID tersedia.
- [ ] IP Address dapat dicatat.
- [ ] User Agent dapat dicatat.
- [ ] Severity tersedia.
- [ ] Audit List tersedia.
- [ ] Filtering tersedia.
- [ ] Search tersedia.
- [ ] Audit Detail tersedia.
- [ ] Change Comparison tersedia.
- [ ] Entity Timeline tersedia.
- [ ] Export tersedia.
- [ ] Retention Policy tersedia.
- [ ] Archive tersedia.
- [ ] Integrity Check structure tersedia.
- [ ] Permission diterapkan.
- [ ] Organization isolation diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 17AA — DEFINITION OF DONE

Modul Audit Trail dianggap selesai apabila:

1. Audit Event dapat diterima dari seluruh module.
2. Audit Number dibuat otomatis.
3. Audit Record bersifat immutable.
4. Append only principle diterapkan.
5. Actor dapat dicatat.
6. System Actor tersedia.
7. Organization dicatat.
8. Module dicatat.
9. Entity dan Entity Reference dicatat.
10. Action dicatat.
11. Old Value dan New Value tersedia.
12. Sensitive Data disanitasi.
13. Password, Token, Secret, dan Credential tidak tersimpan dalam audit.
14. Request ID tersedia.
15. IP Address dan User Agent dapat dicatat.
16. Severity tersedia.
17. Audit Log dapat dicari.
18. Filtering tersedia.
19. Audit Detail tersedia.
20. Change Comparison tersedia.
21. Entity Audit Timeline tersedia.
22. Export tersedia.
23. Retention Policy tersedia.
24. Archive tersedia.
25. Integrity Check structure tersedia.
26. Permission berjalan.
27. Organization isolation berjalan.
28. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- Hash Chain.
- Cryptographic Audit Signature.
- Blockchain Anchoring.
- External Immutable Storage.
- SIEM Integration.
- Security Information Monitoring.
- Anomaly Detection.
- AI Fraud Detection.
- Suspicious Activity Detection.
- Real Time Security Alert.
- Audit Dashboard Analytics.
- Compliance Report.
- Automatic Audit Report.
- WORM Storage.
- Advanced Retention Policy.
- Legal Hold.
- External Audit API.
- Tamper Detection Alert.
- Distributed Audit Ledger.

---

# END OF PRD MODULE 17 — AUDIT TRAIL