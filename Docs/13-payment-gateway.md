# PRD MODULE 13 — PAYMENT GATEWAY

Project: ZETRA
Module: Payment Gateway
Module Code: PAY
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 02-organization-amil.md
- 07-fund-management.md
- 08-accounting-ledger.md

Related Modules:

- 04-zakat-collection.md
- 05-infaq-sedekah.md
- 06-donation.md
- 12-distribution.md
- 14-notification.md
- 15-reporting.md

---

# PRD 13A — OVERVIEW

## 1. Purpose

Modul Payment Gateway bertanggung jawab untuk integrasi pembayaran digital dengan provider eksternal.

Versi awal dibuat sederhana.

Fokus utama:

- Payment Provider Configuration
- Payment Transaction
- Payment Link
- Payment Status
- Webhook Handling
- Payment Verification
- Payment Reference
- Basic Reconciliation

Modul ini tidak menangani:

- Fund Management secara penuh
- Accounting Ledger secara langsung
- Complex settlement
- Multi-provider routing
- Advanced fraud detection

Fitur tersebut dapat dikembangkan pada versi berikutnya.

---

## 2. Core Principle

Payment Gateway hanya bertanggung jawab terhadap proses pembayaran.

Contoh flow:

Muzakki

↓

Create Payment

↓

Payment Gateway

↓

Payment Pending

↓

Customer Pays

↓

Webhook Received

↓

Payment Verified

↓

Collection Recorded

↓

Fund Management

↓

Accounting Event

Payment Gateway tidak langsung mengubah Ledger.

Payment Gateway menghasilkan event yang dapat diproses oleh:

Collection Module

Fund Management

Accounting & Ledger

---

## 3. Goals

Versi awal harus mampu:

1. Menyimpan konfigurasi Payment Provider.
2. Mendukung satu atau lebih Provider.
3. Membuat Payment Transaction.
4. Menghasilkan Payment Reference.
5. Menyimpan Provider Reference.
6. Menyimpan Payment URL.
7. Menyimpan Payment Status.
8. Menerima Webhook.
9. Memverifikasi Webhook.
10. Mencegah duplicate webhook processing.
11. Menyimpan raw payload secara aman.
12. Menghubungkan Payment dengan transaksi internal.
13. Mendukung payment expiration.
14. Mendukung manual payment verification.
15. Menyediakan audit trail.

---

# PRD 13B — PROVIDER

## 4. Provider Entity

Entity:

payment_providers

Fields:

id

organization_id

provider_code

name

driver

status

config_encrypted

webhook_secret_encrypted

sandbox_mode

created_at

updated_at

---

## 5. Provider Code

Contoh:

MIDTRANS

XENDIT

DUITKU

MANUAL

CUSTOM

Provider Code harus:

- uppercase;
- unique dalam organization;
- tidak menggunakan dash.

---

## 6. Driver Principle

Implementasi provider menggunakan driver atau adapter.

Contoh interface:

PaymentProviderInterface

Methods:

createPayment()

getPaymentStatus()

verifyWebhook()

refund()

Driver awal dapat dibuat:

ManualPaymentDriver

Provider lain ditambahkan kemudian.

---

# PRD 13C — PAYMENT TRANSACTION

## 7. Entity

payments

Fields:

id

organization_id

payment_number

provider_id

provider_reference

internal_reference

source_type

source_id

payer_name

payer_email

payer_phone

amount

currency

payment_method

payment_url

expires_at

paid_at

status

metadata

created_at

updated_at

---

## 8. Payment Number

Format:

PAY{YEAR}{SEQUENCE}

Contoh:

PAY2026000001

PAY2026000002

Rules:

- unique;
- immutable;
- human readable;
- tidak menggunakan dash.

Primary key menggunakan:

ULID.

---

# PRD 13D — PAYMENT SOURCE

## 9. Source Type

Payment dapat berasal dari modul lain.

Contoh:

ZAKAT

INFAQ

SEDEKAH

DONATION

CAMPAIGN

OTHER

---

## 10. Source Reference

Payment harus dapat menyimpan:

source_type

source_id

Contoh:

source_type:

ZAKAT

source_id:

01HXYZABC123

Tujuannya agar Payment Gateway tidak memiliki dependency langsung terhadap struktur internal setiap modul.

---

# PRD 13E — PAYMENT METHOD

## 11. Initial Payment Method

BANK_TRANSFER

VIRTUAL_ACCOUNT

EWALLET

QRIS

CARD

CASH

MANUAL

OTHER

Payment method yang tersedia tergantung provider.

---

# PRD 13F — PAYMENT STATUS

## 12. Status

CREATED

PENDING

PAID

FAILED

EXPIRED

CANCELLED

REFUNDED

---

## 13. Status Flow

CREATED

↓

PENDING

↓

PAID

atau:

FAILED

atau:

EXPIRED

atau:

CANCELLED

Jika payment telah PAID dan dilakukan pengembalian:

PAID

↓

REFUNDED

---

# PRD 13G — CREATE PAYMENT

## 14. Flow

Create Internal Transaction

↓

Create Payment

↓

Select Provider

↓

Send Request to Provider

↓

Receive Provider Response

↓

Store:

Provider Reference

Payment URL

Expiration

Payment Method

↓

Status PENDING

---

## 15. Create Payment Rule

Sebelum membuat payment:

- amount harus lebih dari 0;
- source transaction harus valid;
- organization harus valid;
- provider harus active;
- currency harus valid.

---

# PRD 13H — PAYMENT WEBHOOK

## 16. Purpose

Webhook digunakan untuk menerima perubahan status dari Payment Provider.

Contoh:

Payment Success

↓

Provider

↓

Webhook Endpoint

↓

Verify Signature

↓

Validate Payload

↓

Check Duplicate Event

↓

Update Payment

↓

Create Domain Event

---

## 17. Endpoint

POST

/api/v1/webhooks/payments/{provider}

Endpoint harus:

- provider specific;
- tidak membutuhkan user authentication;
- menggunakan signature verification;
- memiliki rate protection;
- mencatat request secara aman.

---

# PRD 13I — WEBHOOK ENTITY

## 18. Entity

payment_webhooks

Fields:

id

provider_id

event_id

event_type

signature_valid

payload

received_at

processed_at

status

error_message

created_at

updated_at

---

## 19. Webhook Status

RECEIVED

PROCESSING

PROCESSED

FAILED

IGNORED

---

# PRD 13J — IDEMPOTENCY

## 20. Purpose

Provider dapat mengirim webhook lebih dari satu kali.

Sistem harus memastikan event tidak diproses berulang.

Unique Key dapat menggunakan:

provider_id

+

event_id

atau:

provider_reference

+

event_type

Jika event sudah diproses:

Return success response tanpa memproses ulang transaksi.

---

# PRD 13K — PAYMENT VERIFICATION

## 21. Automatic Verification

Jika Webhook valid:

Verify Signature

↓

Validate Provider Reference

↓

Validate Amount

↓

Validate Currency

↓

Check Payment Status

↓

Update Payment

↓

Trigger Domain Event

---

## 22. Manual Verification

Admin dengan permission dapat melakukan:

Manual Verify

untuk kondisi tertentu.

Manual Verification wajib memiliki:

Reason

Verified By

Verified At

Audit Trail

---

# PRD 13L — PAYMENT EVENT

## 23. Domain Events

Minimal:

payment_created

payment_pending

payment_paid

payment_failed

payment_expired

payment_cancelled

payment_refunded

payment_webhook_received

payment_webhook_processed

payment_manually_verified

---

## 24. Integration

Ketika:

payment_paid

maka sistem dapat mengirim event ke source module.

Contoh:

Payment

↓

Zakat Collection

↓

Create Collection Record

↓

Fund Management

↓

Accounting Event

Payment Gateway tidak boleh membuat transaksi zakat sendiri tanpa source transaction yang valid.

---

# PRD 13M — PAYMENT EXPIRATION

## 25. Expiration

Payment dapat memiliki:

expires_at

Jika:

current_time > expires_at

dan status:

PENDING

maka status menjadi:

EXPIRED

Expired Payment tidak dapat diproses ulang.

Jika ingin membayar kembali:

Create New Payment.

---

# PRD 13N — PAYMENT FAILURE

## 26. Failure Reason

Contoh:

PROVIDER_ERROR

PAYMENT_DECLINED

PAYMENT_TIMEOUT

INVALID_ACCOUNT

INVALID_AMOUNT

WEBHOOK_INVALID

SIGNATURE_INVALID

OTHER

---

# PRD 13O — REFUND

## 27. Initial Scope

Refund disiapkan secara struktur.

Implementasi awal:

Manual Refund Request.

Entity:

payment_refunds

Fields:

id

payment_id

refund_number

amount

reason

status

requested_by

approved_by

requested_at

processed_at

created_at

updated_at

---

## 28. Refund Status

REQUESTED

APPROVED

PROCESSING

COMPLETED

FAILED

REJECTED

---

## 29. Refund Rule

Refund tidak boleh:

lebih besar dari paid amount.

Payment yang belum PAID:

tidak dapat direfund.

Provider integration dapat ditambahkan kemudian.

---

# PRD 13P — BASIC RECONCILIATION

## 30. Purpose

Versi awal menyediakan reconciliation sederhana.

Bandingkan:

Internal Payment

dengan:

Provider Payment Status.

Status:

MATCHED

MISMATCHED

PENDING_REVIEW

---

## 31. Entity

payment_reconciliations

Fields:

id

payment_id

provider_reference

internal_amount

provider_amount

internal_status

provider_status

result

reconciled_at

reconciled_by

created_at

updated_at

---

# PRD 13Q — API SPECIFICATION

## 32. Payment Provider

GET

/api/v1/payment-providers

POST

/api/v1/payment-providers

GET

/api/v1/payment-providers/{id}

PATCH

/api/v1/payment-providers/{id}

POST

/api/v1/payment-providers/{id}/activate

POST

/api/v1/payment-providers/{id}/deactivate

---

## 33. Payments

GET

/api/v1/payments

POST

/api/v1/payments

GET

/api/v1/payments/{id}

POST

/api/v1/payments/{id}/verify

POST

/api/v1/payments/{id}/cancel

POST

/api/v1/payments/{id}/refresh-status

---

## 34. Refund

GET

/api/v1/payments/{id}/refunds

POST

/api/v1/payments/{id}/refunds

POST

/api/v1/payment-refunds/{id}/approve

POST

/api/v1/payment-refunds/{id}/reject

---

# PRD 13R — PERMISSIONS

## 35. Permission Codes

payment.view

payment.create

payment.verify

payment.cancel

payment.refresh

payment.refund.request

payment.refund.approve

payment.refund.reject

payment.provider.view

payment.provider.manage

payment.webhook.view

payment.reconciliation.view

payment.reconciliation.manage

payment.audit.view

---

# PRD 13S — AUDIT EVENTS

## 36. Audit Events

Minimal:

payment_provider_created

payment_provider_updated

payment_provider_activated

payment_provider_deactivated

payment_created

payment_pending

payment_paid

payment_failed

payment_expired

payment_cancelled

payment_webhook_received

payment_webhook_verified

payment_webhook_failed

payment_manually_verified

payment_refund_requested

payment_refund_approved

payment_refund_completed

payment_reconciliation_created

payment_reconciliation_mismatched

---

# PRD 13T — UI REQUIREMENTS

## 37. Payment Dashboard

Cards:

Pending Payments

Paid Today

Failed Payments

Expired Payments

Total Payment Amount

Provider Status

---

## 38. Payment List

ZETRA DataTable.

Columns:

Payment Number

Source

Payer

Provider

Method

Amount

Created Date

Paid Date

Status

Actions

---

## 39. Payment Detail

Header:

Payment Number

Amount

Status

Provider

Payment Method

Tabs:

Overview

Source Transaction

Provider Response

Webhook History

Reconciliation

Refund

Timeline

Audit

---

## 40. Provider Settings

Features:

Provider List

Enable

Disable

Sandbox Mode

Configuration

Webhook Configuration

Connection Test

Provider Credentials harus:

- encrypted;
- tidak ditampilkan kembali secara penuh;
- hanya dapat diubah oleh authorized user.

---

# PRD 13U — BUSINESS RULES

## 41. General Rules

1. Payment Number harus unik.
2. Payment harus memiliki Source Transaction.
3. Amount harus lebih besar dari 0.
4. Payment Provider harus aktif.
5. Provider credentials harus terenkripsi.
6. Payment PAID tidak dapat diubah kembali menjadi PENDING.
7. Duplicate Webhook tidak boleh memproses transaksi dua kali.
8. Webhook harus diverifikasi.
9. Invalid Webhook harus dicatat.
10. Payment EXPIRED tidak dapat digunakan kembali.
11. Payment ulang harus membuat Payment baru.
12. Manual Verification membutuhkan permission.
13. Manual Verification membutuhkan reason.
14. Payment Gateway tidak langsung membuat Ledger Entry.
15. Payment PAID menghasilkan Domain Event.
16. Source Module bertanggung jawab terhadap bisnis transaksi.
17. Refund tidak boleh melebihi paid amount.
18. Organization isolation wajib diterapkan.
19. Permission diperiksa di backend.
20. Semua perubahan status material harus diaudit.

---

# PRD 13V — TESTING REQUIREMENTS

## 42. Unit Test

Minimal:

- Payment Number Generation
- Payment Creation
- Provider Selection
- Payment Expiration
- Status Transition
- Webhook Signature Verification
- Duplicate Webhook Prevention
- Payment Amount Validation
- Payment Source Validation
- Manual Verification
- Refund Validation
- Reconciliation Result

---

## 43. Integration Test

Flow:

Create Zakat Transaction

↓

Create Payment

↓

Provider Response

↓

Payment Pending

↓

Webhook Received

↓

Signature Verified

↓

Payment Paid

↓

Domain Event

↓

Collection Created

↓

Fund Updated

↓

Accounting Event Created

---

## 44. Security Test

Test:

- Invalid webhook signature;
- Duplicate webhook;
- Cross organization payment access;
- Provider credential exposure;
- Unauthorized manual verification;
- Amount manipulation;
- Payment status manipulation;
- Unauthorized refund;
- Webhook replay attack;
- Audit bypass.

---

# PRD 13W — ACCEPTANCE CRITERIA

- [ ] Payment Provider dapat dikonfigurasi.
- [ ] Provider dapat diaktifkan dan dinonaktifkan.
- [ ] Payment dapat dibuat.
- [ ] Payment Number otomatis dibuat.
- [ ] Payment terhubung dengan Source Transaction.
- [ ] Provider Reference dapat disimpan.
- [ ] Payment URL dapat disimpan.
- [ ] Payment Status tersedia.
- [ ] Webhook Endpoint tersedia.
- [ ] Webhook Signature dapat diverifikasi.
- [ ] Duplicate Webhook dicegah.
- [ ] Payment Expiration tersedia.
- [ ] Manual Verification tersedia.
- [ ] Refund structure tersedia.
- [ ] Basic Reconciliation tersedia.
- [ ] Domain Event tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 13X — DEFINITION OF DONE

Modul Payment Gateway dianggap selesai apabila:

1. Payment Provider dapat dikonfigurasi.
2. Provider abstraction tersedia.
3. Payment dapat dibuat.
4. Payment memiliki nomor unik.
5. Payment dapat terhubung dengan transaksi internal.
6. Provider Reference dapat disimpan.
7. Payment URL dapat digunakan.
8. Payment Status dapat diperbarui.
9. Webhook dapat diterima.
10. Webhook Signature dapat diverifikasi.
11. Duplicate Webhook tidak diproses ulang.
12. Payment Expiration berjalan.
13. Manual Verification tersedia.
14. Basic Reconciliation tersedia.
15. Refund structure tersedia.
16. Payment PAID menghasilkan Domain Event.
17. Source Module dapat menerima Payment Event.
18. Payment Gateway tidak langsung membuat Ledger.
19. Audit Trail tersedia.
20. Organization isolation berjalan.
21. Permission berjalan.
22. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut tidak wajib pada versi awal:

- Multiple Provider Routing
- Smart Provider Selection
- Provider Failover
- Split Payment
- Subscription Payment
- Recurring Payment
- Automatic Refund
- Advanced Settlement
- Advanced Reconciliation
- Payment Analytics
- Fraud Detection
- Fraud Scoring
- Payment Retry Automation
- Saved Payment Method
- Customer Wallet
- Multi Currency Settlement

---

# END OF PRD MODULE 13 — PAYMENT GATEWAY