# PRD MODULE 12 — DISTRIBUTION

Project: ZETRA
Module: Distribution
Module Code: DST
Version: 0.1.0
Status: In progress (Core only; PRD not complete)

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 07-fund-management.md
- 08-accounting-ledger.md
- 09-mustahik.md
- 10-assessment.md
- 11-program-management.md

Related Modules:

- 13-monitoring-evaluation.md
- 14-reporting.md
- 15-public-transparency.md

---

# PRD 12A — OVERVIEW

## 1. Purpose

Modul Distribution bertanggung jawab untuk mengelola proses penyaluran dana atau manfaat kepada Mustahik.

Distribution merupakan proses realisasi penyaluran setelah Mustahik memenuhi kriteria yang diperlukan.

Modul ini menjadi penghubung antara:

Mustahik

Assessment

Program

Fund Management

Accounting & Ledger

Distribution dapat berupa:

- Cash
- Bank Transfer
- Goods
- Service
- Voucher
- Scholarship
- Business Capital
- Other Benefit

Distribution harus memiliki jejak yang dapat ditelusuri dari:

Fund Source

↓

Program

↓

Mustahik

↓

Assessment

↓

Approval

↓

Distribution

↓

Proof of Receipt

↓

Accounting Record

---

## 2. Goals

Modul harus mampu:

1. Membuat Distribution Request.
2. Mendukung distribution berbasis Program.
3. Mendukung direct distribution.
4. Menghubungkan Mustahik.
5. Menghubungkan Assessment.
6. Menghubungkan Fund.
7. Menghubungkan Budget Program.
8. Mendukung berbagai Distribution Type.
9. Mengelola Approval.
10. Mengelola Fund Reservation.
11. Melakukan Fund Deduction.
12. Menghasilkan Accounting Event.
13. Mendukung Batch Distribution.
14. Mendukung Partial Distribution.
15. Mendukung Distribution Schedule.
16. Mendukung Proof of Receipt.
17. Mendukung Failed Distribution.
18. Mendukung Cancellation.
19. Mendukung Reversal.
20. Menyediakan Distribution History.
21. Menyediakan traceability penuh.
22. Menyediakan audit trail.

---

# PRD 12B — CORE PRINCIPLE

## 3. Distribution Principle

Distribution adalah realisasi penyaluran.

Distribution tidak boleh hanya berupa catatan nominal.

Setiap Distribution harus dapat menjawab:

Dana berasal dari mana?

Siapa penerimanya?

Untuk program apa?

Berdasarkan assessment apa?

Siapa yang menyetujui?

Kapan disalurkan?

Bagaimana metode penyalurannya?

Apakah penerima telah menerima?

---

## 4. Separation Principle

Program:

menentukan tujuan dan perencanaan.

Assessment:

menentukan hasil penilaian.

Fund Management:

mengelola saldo dana.

Distribution:

merealisasikan penyaluran.

Accounting:

mencatat transaksi keuangan.

Distribution tidak boleh langsung memodifikasi Ledger.

Distribution menghasilkan:

Accounting Event.

---

# PRD 12C — DISTRIBUTION ENTITY

## 5. Entity

distributions

Fields:

id

organization_id

distribution_number

distribution_type

source_type

program_id

program_enrollment_id

mustahik_id

assessment_id

fund_id

currency

requested_amount

approved_amount

distributed_amount

distribution_date

scheduled_date

status

priority

description

created_by

created_at

updated_at

---

## 6. Distribution Number

Format:

DST{YEAR}{SEQUENCE}

Contoh:

DST2026000001

DST2026000002

DST2026000003

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash;
- tidak digunakan kembali.

Primary key menggunakan:

ULID.

---

# PRD 12D — DISTRIBUTION TYPE

## 7. Initial Distribution Type

CASH

BANK_TRANSFER

GOODS

SERVICE

VOUCHER

SCHOLARSHIP

BUSINESS_CAPITAL

EMERGENCY

OTHER

---

## 8. Cash

Dana diberikan secara tunai.

Wajib memiliki:

Distribution Proof

Receiver Confirmation

Recipient Identity Verification apabila diperlukan.

---

## 9. Bank Transfer

Dana dikirim ke rekening penerima.

Wajib memiliki:

Account Reference

Transfer Reference

Transfer Date

Transfer Status.

Nomor rekening harus dimasking untuk user tanpa permission khusus.

---

## 10. Goods

Distribution berupa barang.

Contoh:

Food Package

School Equipment

Medical Equipment

Business Equipment.

Distribution Amount dapat menggunakan:

Estimated Value

atau:

Actual Procurement Value.

---

# PRD 12E — DISTRIBUTION SOURCE

## 11. Source Type

PROGRAM

DIRECT

EMERGENCY

CAMPAIGN

OTHER

---

## 12. Program Distribution

Distribution berasal dari:

Program

↓

Approved Enrollment

↓

Eligible Mustahik

↓

Fund

↓

Distribution

---

## 13. Direct Distribution

Distribution dapat dilakukan tanpa Program.

Namun tetap membutuhkan:

Mustahik

Fund

Reason

Approval

Distribution Record.

---

# PRD 12F — DISTRIBUTION REQUEST

## 14. Purpose

Distribution Request digunakan sebelum dana atau manfaat benar-benar disalurkan.

Flow:

Create Request

↓

Validate

↓

Fund Availability Check

↓

Assessment Check

↓

Approval

↓

Fund Reservation

↓

Ready for Distribution

---

## 15. Entity

distribution_requests

Fields:

id

organization_id

request_number

mustahik_id

program_id

assessment_id

fund_id

distribution_type

requested_amount

currency

reason

priority

requested_by

requested_at

status

created_at

updated_at

---

## 16. Request Number

Format:

DSR{YEAR}{SEQUENCE}

Contoh:

DSR2026000001

DSR2026000002

---

# PRD 12G — REQUEST VALIDATION

## 17. Validation

Minimal validation:

Mustahik exists

Mustahik active

Mustahik eligible apabila diperlukan

Fund active

Fund sufficient

Program active apabila menggunakan Program

Budget available apabila menggunakan Program

Assessment valid apabila diwajibkan

No conflicting active distribution

Permission valid

---

## 18. Eligibility Rule

Organization dapat menentukan apakah Distribution Type tertentu membutuhkan:

Assessment Required

atau:

Assessment Optional.

Contoh:

Emergency Distribution dapat menggunakan simplified assessment.

---

# PRD 12H — FUND RESERVATION

## 19. Purpose

Fund Reservation digunakan untuk mencegah over-distribution.

Contoh:

Available Fund:

Rp100.000.000

Approved Distribution:

Rp10.000.000

Setelah reservation:

Available:

Rp90.000.000

Reserved:

Rp10.000.000

Actual Balance belum dianggap disbursed sampai Distribution Completed.

---

## 20. Entity

distribution_reservations

Fields:

id

distribution_id

fund_id

reserved_amount

currency

reserved_at

released_at

status

created_at

updated_at

---

## 21. Reservation Status

ACTIVE

RELEASED

CONSUMED

EXPIRED

CANCELLED

---

# PRD 12I — DISTRIBUTION APPROVAL

## 22. Approval Requirement

Distribution dapat membutuhkan approval berdasarkan:

Distribution Type

Amount

Fund

Program

Organization Policy

Risk Level

---

## 23. Approval Flow

DRAFT

↓

PENDING_APPROVAL

↓

APPROVED

↓

RESERVED

↓

SCHEDULED

↓

PROCESSING

↓

COMPLETED

---

## 24. Maker Checker

Jika Maker Checker aktif:

Creator

tidak boleh menyetujui Distribution sendiri.

---

# PRD 12J — DISTRIBUTION STATUS

## 25. Status

DRAFT

PENDING_APPROVAL

APPROVED

RESERVED

SCHEDULED

PROCESSING

COMPLETED

PARTIALLY_COMPLETED

FAILED

CANCELLED

REVERSED

---

## 26. Status Flow

DRAFT

↓

PENDING_APPROVAL

↓

APPROVED

↓

RESERVED

↓

SCHEDULED

↓

PROCESSING

↓

COMPLETED

Jika gagal:

PROCESSING

↓

FAILED

↓

Retry

atau:

CANCELLED

Jika sudah completed dan harus dikoreksi:

COMPLETED

↓

REVERSED

---

# PRD 12K — DISTRIBUTION ITEM

## 27. Purpose

Distribution dapat memiliki satu atau lebih item.

Terutama untuk:

GOODS

SERVICE

VOUCHER

Package Assistance.

---

## 28. Entity

distribution_items

Fields:

id

distribution_id

item_code

item_name

description

quantity

unit

unit_value

total_value

created_at

updated_at

---

# PRD 12L — CASH DISTRIBUTION

## 29. Entity

distribution_cash_details

Fields:

id

distribution_id

amount

currency

cashier_id

disbursed_at

receipt_number

created_at

updated_at

---

## 30. Cash Rule

Cash Distribution wajib memiliki:

Actual Amount

Disbursement Date

Disbursed By

Proof of Receipt.

---

# PRD 12M — BANK TRANSFER

## 31. Entity

distribution_bank_transfers

Fields:

id

distribution_id

bank_name

account_holder_name

account_number_encrypted

account_number_masked

transfer_reference

transfer_amount

transfer_date

status

failure_reason

created_at

updated_at

---

## 32. Bank Transfer Status

PENDING

PROCESSING

SUCCESS

FAILED

REVERSED

---

# PRD 12N — DISTRIBUTION SCHEDULE

## 33. Purpose

Distribution dapat dijadwalkan.

Contoh:

Monthly Scholarship

Quarterly Assistance

Installment Support.

---

## 34. Entity

distribution_schedules

Fields:

id

distribution_id

schedule_type

scheduled_date

amount

status

processed_at

created_at

updated_at

---

## 35. Schedule Type

ONE_TIME

MONTHLY

QUARTERLY

CUSTOM

---

# PRD 12O — PARTIAL DISTRIBUTION

## 36. Purpose

Approved Distribution dapat direalisasikan sebagian.

Contoh:

Approved:

Rp10.000.000

First Distribution:

Rp4.000.000

Second Distribution:

Rp6.000.000

---

## 37. Rule

Total distributed_amount tidak boleh melebihi:

approved_amount.

Jika seluruh nominal selesai:

COMPLETED.

Jika sebagian:

PARTIALLY_COMPLETED.

---

# PRD 12P — BATCH DISTRIBUTION

## 38. Purpose

Batch Distribution digunakan untuk menyalurkan bantuan kepada banyak Mustahik.

Contoh:

100 penerima Beasiswa.

---

## 39. Entity

distribution_batches

Fields:

id

organization_id

batch_number

name

program_id

fund_id

distribution_type

total_amount

total_beneficiary

status

created_by

created_at

updated_at

---

## 40. Batch Number

Format:

DTB{YEAR}{SEQUENCE}

Contoh:

DTB2026000001

DTB2026000002

---

## 41. Batch Flow

Create Batch

↓

Add Beneficiaries

↓

Validate Each Beneficiary

↓

Calculate Total

↓

Approval

↓

Reserve Fund

↓

Process Distribution

↓

Completed

---

# PRD 12Q — DISTRIBUTION BENEFICIARY

## 42. Entity

distribution_beneficiaries

Fields:

id

distribution_id

mustahik_id

approved_amount

distributed_amount

status

failure_reason

created_at

updated_at

---

# PRD 12R — PROOF OF RECEIPT

## 43. Purpose

Setiap Distribution Completed harus dapat memiliki bukti.

Evidence dapat berupa:

Signature

Photo

Document

Receipt

Transfer Proof

QR Confirmation

Other.

---

## 44. Entity

distribution_proofs

Fields:

id

distribution_id

proof_type

file_id

reference_number

verified_by

verified_at

created_at

updated_at

---

## 45. Proof Type

SIGNATURE

PHOTO

RECEIPT

BANK_TRANSFER

DOCUMENT

QR_CONFIRMATION

OTHER

---

# PRD 12S — RECIPIENT CONFIRMATION

## 46. Purpose

Mustahik dapat memberikan konfirmasi penerimaan.

Entity:

distribution_confirmations

Fields:

id

distribution_id

confirmation_method

confirmed_at

confirmed_by

confirmation_data

status

created_at

updated_at

---

## 47. Confirmation Method

SIGNATURE

OTP

QR

PHOTO

MANUAL

OTHER

---

# PRD 12T — DISTRIBUTION FAILURE

## 48. Failure

Distribution dapat gagal karena:

BANK_ACCOUNT_INVALID

RECIPIENT_NOT_FOUND

RECIPIENT_REJECTED

INSUFFICIENT_FUND

TRANSFER_FAILED

DOCUMENT_MISSING

VERIFICATION_FAILED

SYSTEM_ERROR

OTHER

---

## 49. Failure Flow

PROCESSING

↓

FAILED

↓

Retry

atau:

Cancel

Jika dana telah di-reserve:

Release Reservation.

---

# PRD 12U — CANCELLATION

## 50. Cancellation Rule

Distribution yang belum COMPLETED dapat dibatalkan.

Cancellation wajib memiliki:

reason

cancelled_by

cancelled_at.

Jika Fund Reservation masih ACTIVE:

Release Reservation.

---

# PRD 12V — REVERSAL

## 51. Purpose

Distribution yang telah COMPLETED tidak boleh dihapus.

Jika harus dibatalkan secara administratif atau finansial:

Create Reversal.

---

## 52. Reversal Flow

Completed Distribution

↓

Create Reversal Request

↓

Approval

↓

Fund Return apabila berlaku

↓

Accounting Reversal Event

↓

Distribution Status REVERSED

---

## 53. Reversal Requirement

Reversal wajib memiliki:

Reason

Reference

Approved By

Audit Trail.

---

# PRD 12W — FUND INTEGRATION

## 54. Fund Movement

Distribution tidak langsung mengurangi saldo tanpa melalui Fund Management.

Flow:

Distribution Approved

↓

Fund Reserved

↓

Distribution Completed

↓

Fund Consumption

↓

Accounting Event

↓

Journal Posted

---

## 55. Failed Distribution

Jika:

Distribution Failed

maka:

Reservation Released.

Jika transfer sudah keluar namun status belum final:

Gunakan reconciliation process.

---

# PRD 12X — ACCOUNTING INTEGRATION

## 56. Accounting Event

Distribution Completed menghasilkan:

DISTRIBUTIONCOMPLETED.

Contoh rule:

Debit:

Distribution Expense atau Fund Utilization Account.

Credit:

Cash atau Bank Account.

Rule dapat dikonfigurasi pada Accounting Module.

---

## 57. Reversal Event

Distribution Reversed menghasilkan:

DISTRIBUTIONREVERSED.

Accounting Module membuat Journal Reversal berdasarkan rule.

---

# PRD 12Y — PROGRAM INTEGRATION

## 58. Program Distribution

Jika Distribution berasal dari Program:

Validate:

Program ACTIVE.

Enrollment ACTIVE.

Program Budget Available.

Eligible Fund.

---

## 59. Budget Update

Ketika Distribution Completed:

Program:

Committed Amount

berkurang.

Disbursed Amount

bertambah.

Update harus melalui transactional process atau event yang konsisten.

---

# PRD 12Z — MUSTAHIK HISTORY

## 60. Distribution History

Mustahik Profile harus dapat menampilkan:

Distribution Number

Program

Fund

Distribution Type

Amount

Date

Status

Proof

---

# PRD 12AA — API SPECIFICATION

## 61. Distribution

GET

/api/v1/distributions

POST

/api/v1/distributions

GET

/api/v1/distributions/{id}

PATCH

/api/v1/distributions/{id}

POST

/api/v1/distributions/{id}/submit

POST

/api/v1/distributions/{id}/approve

POST

/api/v1/distributions/{id}/reserve

POST

/api/v1/distributions/{id}/schedule

POST

/api/v1/distributions/{id}/process

POST

/api/v1/distributions/{id}/complete

POST

/api/v1/distributions/{id}/cancel

POST

/api/v1/distributions/{id}/reverse

---

## 62. Distribution Request

GET

/api/v1/distribution-requests

POST

/api/v1/distribution-requests

GET

/api/v1/distribution-requests/{id}

POST

/api/v1/distribution-requests/{id}/approve

POST

/api/v1/distribution-requests/{id}/reject

---

## 63. Batch Distribution

GET

/api/v1/distribution-batches

POST

/api/v1/distribution-batches

GET

/api/v1/distribution-batches/{id}

POST

/api/v1/distribution-batches/{id}/beneficiaries

POST

/api/v1/distribution-batches/{id}/validate

POST

/api/v1/distribution-batches/{id}/submit

POST

/api/v1/distribution-batches/{id}/approve

POST

/api/v1/distribution-batches/{id}/process

---

## 64. Proof

POST

/api/v1/distributions/{id}/proofs

GET

/api/v1/distributions/{id}/proofs

POST

/api/v1/distributions/{id}/confirm

---

# PRD 12AB — PERMISSIONS

## 65. Permission Codes

distribution.view

distribution.create

distribution.update

distribution.submit

distribution.approve

distribution.reject

distribution.reserve

distribution.schedule

distribution.process

distribution.complete

distribution.cancel

distribution.reverse

distribution.batch.view

distribution.batch.create

distribution.batch.update

distribution.batch.approve

distribution.batch.process

distribution.proof.view

distribution.proof.upload

distribution.proof.verify

distribution.confirm

distribution.export

distribution.audit.view

---

# PRD 12AC — AUDIT EVENTS

## 66. Audit Events

Minimal:

distribution_created

distribution_updated

distribution_submitted

distribution_approved

distribution_rejected

distribution_reserved

distribution_reservation_released

distribution_scheduled

distribution_processing

distribution_completed

distribution_partially_completed

distribution_failed

distribution_retried

distribution_cancelled

distribution_reversal_requested

distribution_reversed

distribution_proof_uploaded

distribution_proof_verified

distribution_recipient_confirmed

distribution_batch_created

distribution_batch_submitted

distribution_batch_approved

distribution_batch_processed

---

# PRD 12AD — UI REQUIREMENTS

## 67. Distribution Dashboard

Cards:

Pending Approval

Reserved Amount

Scheduled Distribution

Processing

Completed Today

Failed Distribution

Total Distributed

Batch Distribution

---

## 68. Distribution List

ZETRA DataTable.

Columns:

Distribution Number

Mustahik

Program

Fund

Distribution Type

Approved Amount

Distributed Amount

Scheduled Date

Status

Actions

---

## 69. Distribution Detail

Header:

Distribution Number

Mustahik

Program

Fund

Status

Amount

Priority

Tabs:

Overview

Approval

Fund Reservation

Distribution Items

Payment

Schedule

Proof

Confirmation

Accounting

Timeline

Audit

---

## 70. Create Distribution

Steps:

Step 1

Select Mustahik

↓

Step 2

Select Source

Program atau Direct

↓

Step 3

Assessment Validation

↓

Step 4

Select Fund

↓

Step 5

Distribution Type

↓

Step 6

Amount atau Items

↓

Step 7

Schedule

↓

Step 8

Review

↓

Save Draft

atau:

Submit for Approval

---

## 71. Batch Distribution UI

Features:

Create Batch

Select Program

Select Fund

Add Mustahik

Import Beneficiary List

Validate Eligibility

Calculate Total

Review Errors

Submit

Approve

Process

Monitor Progress

---

# PRD 12AE — BUSINESS RULES

## 72. General Rules

1. Distribution Number harus unik.
2. Distribution harus memiliki Mustahik.
3. Distribution harus memiliki Fund.
4. Program optional untuk Direct Distribution.
5. Assessment dapat diwajibkan berdasarkan policy.
6. Distribution tidak boleh melebihi Fund Availability.
7. Approved Distribution dapat melakukan Fund Reservation.
8. Reservation harus dilepas jika Distribution gagal atau dibatalkan.
9. Completed Distribution harus mengonsumsi Fund Reservation.
10. Distribution Completed menghasilkan Accounting Event.
11. Distribution tidak langsung membuat Journal.
12. Distribution Completed tidak dapat dihapus.
13. Koreksi dilakukan melalui Reversal.
14. Partial Distribution tidak boleh melebihi Approved Amount.
15. Batch Distribution harus memvalidasi setiap beneficiary.
16. Program Distribution harus memvalidasi Enrollment.
17. Program Distribution harus memperhatikan Budget.
18. Bukti penerimaan dapat diwajibkan berdasarkan Distribution Type.
19. Sensitive Bank Data harus dilindungi.
20. Maker Checker dapat diterapkan.
21. Organization isolation wajib diterapkan.
22. Permission diperiksa di backend.
23. Semua aktivitas material harus diaudit.

---

# PRD 12AF — TESTING REQUIREMENTS

## 73. Unit Test

Minimal:

- Distribution Creation
- Distribution Number Generation
- Mustahik Validation
- Fund Validation
- Assessment Validation
- Program Validation
- Budget Validation
- Fund Reservation
- Reservation Release
- Distribution Approval
- Partial Distribution
- Distribution Completion
- Failed Distribution
- Retry
- Cancellation
- Reversal
- Batch Distribution
- Proof Upload
- Recipient Confirmation
- Accounting Event Creation

---

## 74. Integration Test

Flow:

Mustahik

↓

Assessment Approved

↓

Program Enrollment

↓

Distribution Request

↓

Approval

↓

Fund Reservation

↓

Distribution Processing

↓

Recipient Confirmation

↓

Distribution Completed

↓

Fund Consumed

↓

Accounting Event

↓

Journal Posted

↓

Mustahik History Updated

---

## 75. Security Test

Test:

- Cross organization distribution access;
- Unauthorized approval;
- Fund over-distribution;
- Duplicate distribution completion;
- Invalid fund reservation;
- Unauthorized bank data access;
- Distribution modification after completion;
- Reversal without approval;
- Batch manipulation;
- Proof unauthorized access;
- Audit bypass.

---

# PRD 12AG — ACCEPTANCE CRITERIA

- [ ] Distribution dapat dibuat.
- [ ] Distribution Number otomatis dibuat.
- [ ] Mustahik terhubung.
- [ ] Fund terhubung.
- [ ] Program Distribution tersedia.
- [ ] Direct Distribution tersedia.
- [ ] Assessment Validation tersedia.
- [ ] Fund Availability Check tersedia.
- [ ] Fund Reservation tersedia.
- [ ] Approval tersedia.
- [ ] Cash Distribution tersedia.
- [ ] Bank Transfer tersedia.
- [ ] Goods Distribution tersedia.
- [ ] Partial Distribution tersedia.
- [ ] Scheduled Distribution tersedia.
- [ ] Batch Distribution tersedia.
- [ ] Proof of Receipt tersedia.
- [ ] Recipient Confirmation tersedia.
- [ ] Failed Distribution handling tersedia.
- [ ] Cancellation tersedia.
- [ ] Reversal tersedia.
- [ ] Fund Management integration tersedia.
- [ ] Accounting Event tersedia.
- [ ] Program Budget integration tersedia.
- [ ] Mustahik History tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 12AH — DEFINITION OF DONE

Modul Distribution dianggap selesai apabila:

1. Distribution dapat dibuat.
2. Distribution memiliki nomor unik.
3. Mustahik dapat dipilih.
4. Fund dapat dipilih.
5. Distribution dapat berasal dari Program.
6. Direct Distribution didukung.
7. Assessment dapat divalidasi.
8. Fund Availability dapat diperiksa.
9. Fund Reservation berjalan.
10. Distribution Approval berjalan.
11. Cash Distribution didukung.
12. Bank Transfer didukung.
13. Goods Distribution didukung.
14. Partial Distribution berjalan.
15. Scheduled Distribution berjalan.
16. Batch Distribution berjalan.
17. Proof of Receipt dapat disimpan.
18. Recipient Confirmation tersedia.
19. Failed Distribution dapat ditangani.
20. Cancellation dapat dilakukan.
21. Completed Distribution dapat direversal.
22. Fund Management terintegrasi.
23. Accounting Event dihasilkan.
24. Program Budget diperbarui.
25. Mustahik Distribution History tersedia.
26. Audit Trail tersedia.
27. Organization isolation berjalan.
28. Permission berjalan.
29. Automated Test berhasil.

---

# END OF PRD MODULE 12 — DISTRIBUTION
