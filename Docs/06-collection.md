# PRD MODULE 06 — COLLECTION

Project: ZETRA
Module: Collection
Module Code: COL
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 03-muzaki.md
- 04-zakat.md
- 05-zakat-calculator.md

---

# PRD 06A — OVERVIEW

## 1. Purpose

Modul Collection bertanggung jawab untuk mengelola proses penghimpunan dana zakat dari Muzaki.

Modul ini menjadi sumber utama untuk pencatatan kewajiban atau pembayaran zakat sebelum dana masuk ke proses:

- Payment Processing
- Verification
- Settlement
- Fund Management
- Distribution
- Accounting

Collection dapat berasal dari:

- Hasil Zakat Calculator
- Input langsung oleh Amil
- Pembayaran mandiri oleh Muzaki
- Import transaksi
- Integrasi eksternal

Collection bukan Payment Gateway.

Collection hanya mengelola business transaction penghimpunan.

Pemrosesan metode pembayaran dikelola oleh modul lain.

---

## 2. Goals

Modul harus mampu:

1. Membuat Collection Transaction.
2. Menghubungkan transaksi dengan Muzaki.
3. Menghubungkan transaksi dengan Zakat Type.
4. Menghubungkan transaksi dengan Calculation apabila tersedia.
5. Mendukung transaksi tanpa Calculation untuk kondisi tertentu.
6. Mendukung pembayaran penuh.
7. Mendukung pembayaran sebagian.
8. Mendukung beberapa metode pembayaran.
9. Mendukung beberapa instrumen pembayaran.
10. Mengelola status transaksi.
11. Mengelola reference number.
12. Menyimpan nominal yang menjadi kewajiban.
13. Menyimpan nominal yang telah dibayar.
14. Menghitung sisa pembayaran.
15. Mendukung pembayaran bertahap apabila diizinkan.
16. Mendukung cancellation.
17. Mendukung expiration.
18. Mendukung manual verification.
19. Menyediakan audit trail.
20. Menjadi sumber data untuk Fund Management dan Accounting.

---

# PRD 06B — CORE CONCEPT

## 3. Collection Flow

Muzaki

↓

Zakat Calculation

atau

Manual Collection

↓

Create Collection

↓

Determine Amount

↓

Select Payment Method

↓

Payment Processing

↓

Payment Verification

↓

Collection Updated

↓

Completed

↓

Fund Management

---

## 4. Collection Principle

Collection merupakan business record.

Payment merupakan financial event.

Satu Collection dapat memiliki:

satu atau lebih Payment.

Contoh:

Collection Amount:

Rp10.000.000

Payment 1:

Rp5.000.000

Payment 2:

Rp3.000.000

Payment 3:

Rp2.000.000

Collection:

COMPLETED

---

# PRD 06C — COLLECTION ENTITY

## 5. Entity

collections

Fields:

id

organization_id

collection_number

muzaki_id

calculation_id

zakat_type_id

zakat_rule_id

collection_date

due_date

status

currency

expected_amount

paid_amount

remaining_amount

payment_count

source

notes

created_by

created_at

updated_at

deleted_at

---

## 6. Collection Number

Format:

COL{YEAR}{SEQUENCE}

Contoh:

COL2026000001

COL2026000002

COL2026000003

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash;
- tidak digunakan sebagai primary key;
- tidak boleh digunakan kembali.

Primary key menggunakan:

ULID

---

# PRD 06D — COLLECTION SOURCE

## 7. Source Type

Initial values:

CALCULATOR

MANUAL

SELF_SERVICE

IMPORT

API

INTEGRATION

---

## 8. Source Definition

### CALCULATOR

Collection dibuat berdasarkan hasil:

05-zakat-calculator.md

### MANUAL

Amil membuat Collection secara langsung.

### SELF_SERVICE

Muzaki membuat atau memulai kewajiban pembayaran melalui portal.

### IMPORT

Collection berasal dari proses import.

### API

Collection dibuat oleh API consumer.

### INTEGRATION

Collection berasal dari sistem eksternal.

---

# PRD 06E — COLLECTION STATUS

## 9. Status

Initial values:

DRAFT

PENDING

PARTIALLY_PAID

PAID

COMPLETED

EXPIRED

CANCELLED

REFUNDED

---

## 10. Status Definition

### DRAFT

Collection masih dalam proses.

### PENDING

Menunggu pembayaran.

### PARTIALLY_PAID

Sebagian nominal telah dibayar.

### PAID

Seluruh nominal telah diterima tetapi proses internal belum selesai.

### COMPLETED

Collection selesai dan siap diteruskan ke proses fund management.

### EXPIRED

Masa pembayaran telah berakhir.

### CANCELLED

Collection dibatalkan.

### REFUNDED

Dana telah dikembalikan sesuai proses refund.

---

# PRD 06F — COLLECTION AMOUNT

## 11. Amount Fields

Setiap Collection memiliki:

expected_amount

Jumlah yang diharapkan atau menjadi target pembayaran.

paid_amount

Jumlah pembayaran yang telah berhasil diverifikasi.

remaining_amount

Sisa kewajiban.

Formula:

remaining_amount

=

expected_amount

-

paid_amount

---

## 12. Amount Rule

expected_amount dapat berasal dari:

Calculation Result

atau:

Manual Input

Jika berasal dari Calculation:

expected_amount harus mengambil nilai dari:

calculation final amount

Collection tetap menyimpan snapshot nominal.

Perubahan pada Calculation tidak boleh otomatis mengubah Collection yang telah dibuat.

---

# PRD 06G — COLLECTION ITEM

## 13. Purpose

Collection dapat mendukung satu atau lebih item.

Hal ini diperlukan untuk:

- Multiple Zakat Type
- Multiple obligation
- Zakat dan donasi dalam satu checkout
- Future extensibility

---

## 14. Entity

collection_items

Fields:

id

collection_id

zakat_type_id

calculation_id

description

quantity

unit

expected_amount

paid_amount

remaining_amount

status

created_at

updated_at

---

## 15. Initial Scope

Versi awal sistem dapat membuat:

1 Collection

=

1 Zakat Item

Namun struktur database harus mendukung:

1 Collection

=

Many Collection Items

---

# PRD 06H — PAYMENT ALLOCATION

## 16. Purpose

Satu pembayaran dapat dialokasikan ke satu atau beberapa Collection Item.

---

## 17. Entity

payment_allocations

Fields:

id

payment_id

collection_id

collection_item_id

allocated_amount

currency

created_at

---

## 18. Allocation Rule

Jumlah:

allocated_amount

tidak boleh melebihi:

remaining_amount

kecuali organization mengaktifkan:

OVERPAYMENT_REVIEW

---

# PRD 06I — COLLECTION CREATION

## 19. From Calculator

Flow:

Calculation

Status:

CONFIRMED

↓

Create Collection

↓

Copy Snapshot

↓

Collection:

PENDING

---

## 20. Required Snapshot

Collection harus menyimpan referensi:

calculation_id

zakat_type_id

zakat_rule_id

expected_amount

currency

calculation_number

Calculation snapshot tidak perlu diduplikasi seluruhnya apabila sudah immutable, tetapi Collection harus menyimpan data minimum yang diperlukan untuk menjaga referensi bisnis.

---

## 21. Manual Collection

Amil dapat membuat Collection tanpa Calculation apabila memiliki permission.

Required:

Muzaki

Zakat Type

Amount

Reason

Collection Date

---

## 22. Manual Amount Rule

Jika Collection dibuat manual:

Sistem harus menyimpan:

source = MANUAL

reason

created_by

Manual Collection dapat membutuhkan approval sesuai konfigurasi organisasi.

---

# PRD 06J — PAYMENT METHOD

## 23. Payment Method

Collection dapat mendukung:

CASH

BANK_TRANSFER

VIRTUAL_ACCOUNT

QRIS

EWALLET

CARD

PAYMENT_GATEWAY

OTHER

Payment Method bersifat configurable.

---

## 24. Payment Instrument

Payment Instrument adalah detail dari Payment Method.

Contoh:

BANK_TRANSFER

↓

BSI

BRI

BNI

MANDIRI

QRIS

↓

QRIS_STATIC

QRIS_DYNAMIC

PAYMENT_GATEWAY

↓

Provider tertentu.

Payment Instrument tidak dikelola langsung di Collection.

Konfigurasi lebih lanjut dikelola pada Payment Module.

---

# PRD 06K — PARTIAL PAYMENT

## 25. Purpose

Organization dapat mengizinkan pembayaran bertahap.

Configuration:

allow_partial_payment

Default:

false

---

## 26. Partial Payment Flow

Collection:

Expected:

10000000

↓

Payment:

3000000

↓

Collection:

PARTIALLY_PAID

Remaining:

7000000

↓

Payment:

7000000

↓

Collection:

PAID

---

## 27. Partial Payment Rule

Jika:

allow_partial_payment = false

maka pembayaran harus memenuhi expected amount sesuai aturan payment yang berlaku.

---

# PRD 06L — OVERPAYMENT

## 28. Purpose

Sistem harus menangani pembayaran melebihi nominal Collection.

---

## 29. Overpayment Status

NONE

DETECTED

UNDER_REVIEW

REFUNDED

REALLOCATED

---

## 30. Overpayment Rule

Jika:

Paid Amount

>

Expected Amount

maka:

Collection tidak langsung dianggap COMPLETED.

Sistem membuat:

Overpayment Record

dengan status:

DETECTED

Tindakan selanjutnya:

- Refund
- Reallocate
- Hold

sesuai kebijakan organisasi.

---

# PRD 06M — EXPIRATION

## 31. Due Date

Collection dapat memiliki:

due_date

Jika kosong:

Collection tidak memiliki batas waktu.

---

## 32. Expiration Rule

Jika:

Current Date

>

due_date

dan:

remaining_amount > 0

maka:

EXPIRED

Collection expired tidak dapat menerima payment baru tanpa:

Reactivation

atau:

Recreate Collection

---

## 33. Reactivation

Permission:

collection.reactivate

Reactivation wajib memiliki:

reason

Audit trail.

---

# PRD 06N — CANCELLATION

## 34. Cancellation Rule

Collection dapat dibatalkan apabila:

Status:

DRAFT

atau:

PENDING

atau:

PARTIALLY_PAID sesuai kebijakan.

Jika sudah terdapat payment:

Cancellation dapat memerlukan proses refund atau adjustment.

---

## 35. Cancel Request

POST:

/api/v1/collections/{id}/cancel

Request:

reason

---

## 36. Cancel Restriction

Collection dengan status:

COMPLETED

tidak dapat langsung dibatalkan.

Harus melalui:

Adjustment

atau:

Refund Process

---

# PRD 06O — COLLECTION VERIFICATION

## 37. Purpose

Beberapa pembayaran membutuhkan verifikasi Amil.

Contoh:

- Transfer manual;
- Cash;
- Import transaksi;
- Bukti pembayaran.

---

## 38. Verification Status

PENDING

VERIFIED

REJECTED

---

## 39. Verification Rule

Collection tidak meningkatkan:

paid_amount

berdasarkan payment yang belum:

VERIFIED

atau:

SETTLED

sesuai metode pembayaran.

Source of truth pembayaran berasal dari Payment Module.

---

# PRD 06P — RECEIPT

## 40. Purpose

Receipt diterbitkan setelah pembayaran memenuhi kondisi tertentu.

Receipt dapat diterbitkan ketika:

Collection:

PAID

atau:

COMPLETED

tergantung konfigurasi organisasi.

---

## 41. Receipt Number

Format:

RCT{YEAR}{SEQUENCE}

Contoh:

RCT2026000001

Rules:

- unique;
- immutable;
- tidak menggunakan dash;
- tidak digunakan kembali.

---

## 42. Receipt Data

Receipt minimal:

Receipt Number

Collection Number

Muzaki

Zakat Type

Amount

Payment Date

Organization

Verification Status

Reference

QR Verification Reference

Template dan generation dikelola oleh Document Module.

---

# PRD 06Q — API SPECIFICATION

## 43. Create Collection

POST

/api/v1/collections

Request:

muzaki_id

zakat_type_id

calculation_id

expected_amount

currency

collection_date

due_date

source

notes

---

## 44. List Collections

GET

/api/v1/collections

Filters:

muzaki_id

zakat_type_id

status

source

date_from

date_to

organization_id

---

## 45. Collection Detail

GET

/api/v1/collections/{id}

---

## 46. Update Draft

PATCH

/api/v1/collections/{id}

Hanya dapat dilakukan ketika:

DRAFT

---

## 47. Create from Calculation

POST

/api/v1/collections/from-calculation

Request:

calculation_id

due_date

---

## 48. Confirm Collection

POST

/api/v1/collections/{id}/confirm

Flow:

DRAFT

↓

Validation

↓

PENDING

---

## 49. Cancel Collection

POST

/api/v1/collections/{id}/cancel

---

## 50. Reactivate Collection

POST

/api/v1/collections/{id}/reactivate

---

## 51. Collection Summary

GET

/api/v1/collections/summary

Response:

total_collections

total_expected

total_paid

total_remaining

pending_count

partially_paid_count

completed_count

---

# PRD 06R — PAYMENT EVENT INTEGRATION

## 52. Purpose

Collection menerima event dari Payment Module.

Contoh event:

payment.pending

payment.verified

payment.settled

payment.failed

payment.cancelled

payment.refunded

---

## 53. Payment Settled Flow

Payment:

SETTLED

↓

Create Allocation

↓

Update Collection Item

↓

Update Collection Paid Amount

↓

Calculate Remaining Amount

↓

Update Collection Status

---

## 54. Collection Status Resolution

Jika:

paid_amount = 0

dan belum ada pembayaran:

PENDING

Jika:

0 < paid_amount < expected_amount:

PARTIALLY_PAID

Jika:

paid_amount >= expected_amount:

PAID

Setelah proses internal selesai:

COMPLETED

---

# PRD 06S — FUND HANDOFF

## 55. Purpose

Collection yang selesai harus dapat diteruskan ke Fund Management.

Collection tidak langsung mengatur saldo dana.

Flow:

Collection Completed

↓

Create Fund Inflow Event

↓

Fund Management

↓

Ledger / Accounting

---

## 56. Fund Event Data

Minimal:

collection_id

collection_item_id

payment_id

amount

currency

zakat_type

organization_id

settlement_date

reference

---

# PRD 06T — PERMISSIONS

## 57. Permission Codes

collection.view

collection.create

collection.update

collection.confirm

collection.cancel

collection.reactivate

collection.create_manual

collection.verify

collection.adjust

collection.override

collection.view_payment

collection.view_receipt

collection.export

collection.view_audit

---

## 58. Approval Permissions

Jika workflow approval aktif:

collection.approve

collection.manual.approve

collection.overpayment.approve

collection.refund.approve

---

# PRD 06U — AUDIT EVENTS

## 59. Audit Events

Minimal:

collection_created

collection_updated

collection_confirmed

collection_cancelled

collection_reactivated

collection_expired

collection_payment_received

collection_payment_allocated

collection_partially_paid

collection_paid

collection_completed

collection_overpayment_detected

collection_refund_requested

collection_refunded

collection_converted_from_calculation

receipt_issued

---

## 60. Audit Data

Audit minimal menyimpan:

actor_id

organization_id

action

entity_type

entity_id

request_id

timestamp

reason

before

after

---

# PRD 06V — UI REQUIREMENTS

## 61. Collection List

ZETRA DataTable.

Columns:

Collection Number

Muzaki

Zakat Type

Expected Amount

Paid Amount

Remaining

Status

Source

Collection Date

Actions

---

## 62. Collection Detail

Header:

Collection Number

Status

Muzaki

Expected Amount

Paid Amount

Remaining Amount

Tabs:

Overview

Items

Payments

Allocations

Receipt

History

Audit

---

## 63. Create Collection

Mode:

From Calculation

atau:

Manual

### From Calculation

Select:

Confirmed Calculation

↓

Load Amount

↓

Set Due Date

↓

Create Collection

### Manual

Input:

Muzaki

Zakat Type

Amount

Reason

Date

Due Date

↓

Create

---

## 64. Collection Dashboard

Cards:

Total Expected

Total Collected

Total Remaining

Pending

Partially Paid

Completed

Expired

Charts dapat ditambahkan pada Reporting Module.

---

# PRD 06W — BUSINESS RULES

## 65. General Rules

1. Collection adalah business transaction.
2. Payment adalah financial event.
3. Satu Collection dapat memiliki banyak Payment.
4. Collection dapat memiliki banyak Item.
5. Payment dapat dialokasikan ke Collection Item.
6. Collection dari Calculation menyimpan reference Calculation.
7. Collection snapshot tidak berubah otomatis.
8. Paid Amount hanya berasal dari payment valid.
9. Unverified payment tidak meningkatkan paid amount.
10. Partial payment mengikuti organization configuration.
11. Overpayment harus dideteksi.
12. Collection expired tidak menerima payment baru.
13. Collection completed tidak dapat dibatalkan langsung.
14. Manual Collection wajib mencatat reason.
15. Collection Number immutable.
16. Organization isolation wajib diterapkan.
17. Permission diperiksa di backend.
18. Semua perubahan status harus diaudit.
19. Collection completed menghasilkan Fund Inflow Event.
20. Collection tidak menjadi General Ledger.

---

# PRD 06X — TESTING REQUIREMENTS

## 66. Unit Test

Minimal:

- Create Collection
- Collection Number Generation
- Amount Calculation
- Remaining Amount Calculation
- Partial Payment
- Full Payment
- Overpayment Detection
- Expiration
- Cancellation
- Reactivation
- Status Resolution
- Payment Allocation

---

## 67. Integration Test

Flow:

Create Calculation

↓

Confirm Calculation

↓

Create Collection

↓

Payment Settled

↓

Allocate Payment

↓

Update Collection

↓

Complete Collection

↓

Create Fund Event

---

## 68. Security Test

Test:

- Unauthorized collection access
- Cross organization access
- Manual collection without permission
- Invalid amount
- Duplicate payment allocation
- Over allocation
- Expired collection payment
- Cancellation after completed
- Duplicate collection number

---

# PRD 06Y — ACCEPTANCE CRITERIA

- [ ] Collection dapat dibuat.
- [ ] Collection dapat dibuat dari Calculation.
- [ ] Manual Collection tersedia.
- [ ] Collection Number otomatis dibuat.
- [ ] Collection memiliki Expected Amount.
- [ ] Collection memiliki Paid Amount.
- [ ] Collection memiliki Remaining Amount.
- [ ] Partial Payment didukung.
- [ ] Multiple Payment didukung.
- [ ] Collection Item didukung.
- [ ] Payment Allocation didukung.
- [ ] Overpayment dideteksi.
- [ ] Expiration tersedia.
- [ ] Cancellation tersedia.
- [ ] Reactivation tersedia.
- [ ] Receipt dapat diterbitkan.
- [ ] Payment Event dapat diterima.
- [ ] Collection Status diperbarui otomatis.
- [ ] Fund Inflow Event dibuat.
- [ ] Audit Trail tersedia.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 06Z — DEFINITION OF DONE

Modul Collection dianggap selesai apabila:

1. Collection dapat dibuat dari Calculation.
2. Collection dapat dibuat secara manual.
3. Collection memiliki nomor unik.
4. Expected Amount tersimpan.
5. Paid Amount dapat diperbarui berdasarkan Payment.
6. Remaining Amount dihitung otomatis.
7. Partial Payment berjalan sesuai konfigurasi.
8. Multiple Payment didukung.
9. Payment Allocation berjalan.
10. Overpayment dapat dideteksi.
11. Collection dapat expired.
12. Collection dapat dibatalkan sesuai aturan.
13. Collection dapat di-reactivate.
14. Receipt dapat diterbitkan.
15. Payment Event dapat diproses.
16. Collection dapat menjadi Completed.
17. Fund Inflow Event dapat dibuat.
18. Organization isolation berjalan.
19. Permission berjalan.
20. Audit Trail tersedia.
21. Automated Test berhasil.

---

# END OF PRD MODULE 06 — COLLECTION