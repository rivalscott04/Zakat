# PRD MODULE 14 — Bank Reconciliation

> Status: Draft  
> Version: 0.1.0  
> Module Code: TBD

## 1. Tujuan Modul

TBD

## 2. Latar Belakang

TBD

## 3. Scope

### 3.1 In Scope

TBD
# PRD MODULE 14 — BANK RECONCILIATION

Project: ZETRA
Module: Bank Reconciliation
Module Code: BRC
Version: 0.1.0
Status: Draft

Dependencies:

- 00-core-foundation.md
- 01-authentication-authorization.md
- 07-fund-management.md
- 08-accounting-ledger.md
- 13-payment-gateway.md

Related Modules:

- 04-zakat-collection.md
- 05-infaq-sedekah.md
- 06-donation.md
- 12-distribution.md
- 15-reporting.md

---

# PRD 14A — OVERVIEW

## 1. Purpose

Modul Bank Reconciliation bertanggung jawab untuk mencocokkan transaksi internal ZETRA dengan transaksi yang tercatat pada rekening bank.

Tujuan utama modul:

Memastikan bahwa transaksi yang tercatat dalam sistem sesuai dengan mutasi rekening bank.

Flow utama:

Bank Account

↓

Bank Statement

↓

Import Transaction

↓

Internal Transaction

↓

Matching

↓

Reconciliation

↓

Exception Handling

↓

Accounting Verification

---

## 2. Goals

Modul harus mampu:

1. Mengelola rekening bank organisasi.
2. Membuat Bank Statement.
3. Mengimpor mutasi bank.
4. Mendukung input manual.
5. Menyimpan Bank Transaction.
6. Menampilkan unmatched transaction.
7. Mencocokkan transaksi dengan Payment.
8. Mencocokkan transaksi dengan Collection.
9. Mencocokkan transaksi dengan Distribution.
10. Mendukung manual matching.
11. Mendukung automatic matching sederhana.
12. Menangani mismatch.
13. Menangani duplicate transaction.
14. Menangani unidentified transaction.
15. Mendukung reconciliation period.
16. Menyediakan reconciliation summary.
17. Menyediakan audit trail.

Versi awal tidak wajib mendukung:

- Direct Bank API Integration
- Real-time Bank Sync
- AI Matching
- Advanced Auto Reconciliation
- Multi-currency Complex Settlement

---

# PRD 14B — CORE PRINCIPLE

## 3. Reconciliation Principle

Bank Reconciliation tidak boleh mengubah transaksi bank asli.

Bank Transaction dianggap sebagai data sumber.

Jika terjadi kesalahan:

Tidak boleh menghapus transaksi asli.

Gunakan:

Correction

atau:

Adjustment Reference.

---

## 4. Matching Principle

Satu Bank Transaction dapat:

Tidak memiliki match.

Memiliki satu match.

Memiliki beberapa match.

Contoh:

Satu transfer bank:

Rp1.000.000

dapat berasal dari:

2 transaksi internal:

Rp500.000

+

Rp500.000.

Sistem harus mendukung:

ONE_TO_ONE

ONE_TO_MANY

MANY_TO_ONE.

Versi awal wajib mendukung:

ONE_TO_ONE

Manual MANY_TO_ONE.

---

# PRD 14C — BANK ACCOUNT

## 5. Entity

bank_accounts

Fields:

id

organization_id

account_code

bank_name

account_name

account_number_encrypted

account_number_masked

currency

opening_balance

current_balance

status

created_at

updated_at

---

## 6. Account Code

Format:

BNK{SEQUENCE}

Contoh:

BNK001

BNK002

BNK003

Rules:

- unique dalam organization;
- immutable;
- uppercase;
- tidak menggunakan dash.

---

## 7. Bank Account Status

ACTIVE

INACTIVE

CLOSED

---

## 8. Security Rule

Nomor rekening harus:

- disimpan terenkripsi;
- ditampilkan dalam bentuk masked;
- hanya dapat dilihat penuh oleh permission tertentu.

Contoh:

1234567890

ditampilkan sebagai:

******7890

---

# PRD 14D — BANK STATEMENT

## 9. Purpose

Bank Statement merupakan satu dokumen atau satu periode mutasi rekening.

Contoh:

Bank:

Bank ABC

Account:

BNK001

Period:

1 Januari 2026

sampai

31 Januari 2026.

---

## 10. Entity

bank_statements

Fields:

id

organization_id

bank_account_id

statement_number

period_start

period_end

opening_balance

closing_balance

transaction_count

status

imported_by

imported_at

created_at

updated_at

---

## 11. Statement Number

Format:

BST{YEAR}{SEQUENCE}

Contoh:

BST2026000001

BST2026000002

---

## 12. Statement Status

DRAFT

IMPORTED

PROCESSING

RECONCILING

RECONCILED

CLOSED

---

# PRD 14E — BANK TRANSACTION

## 13. Entity

bank_transactions

Fields:

id

organization_id

bank_statement_id

bank_account_id

transaction_reference

transaction_date

value_date

description

debit_amount

credit_amount

balance

currency

counterparty_name

counterparty_account

raw_data

match_status

created_at

updated_at

---

## 14. Transaction Reference

Reference berasal dari Bank apabila tersedia.

Jika Bank tidak menyediakan unique reference:

Generate internal identifier.

Contoh:

BTX{YEAR}{SEQUENCE}

Contoh:

BTX2026000001

---

## 15. Transaction Direction

Debit:

Dana keluar dari rekening.

Credit:

Dana masuk ke rekening.

Contoh:

Credit:

Pembayaran Zakat.

Debit:

Penyaluran Zakat melalui transfer.

---

# PRD 14F — IMPORT BANK STATEMENT

## 16. Import Method

Versi awal mendukung:

CSV

XLSX

Manual Entry

Import format harus dapat dipetakan.

Contoh kolom:

Transaction Date

Description

Debit

Credit

Balance

Reference

---

## 17. Import Flow

Upload File

↓

Validate File

↓

Preview Data

↓

Map Columns

↓

Validate Transactions

↓

Detect Duplicate

↓

Create Statement

↓

Import Transactions

↓

Start Reconciliation

---

## 18. Import Validation

Minimal:

- Bank Account valid;
- Transaction Date valid;
- Amount valid;
- Currency valid;
- Duplicate detection;
- Statement Period valid;
- File tidak corrupt.

---

# PRD 14G — DUPLICATE DETECTION

## 19. Duplicate Detection

Sistem harus mendeteksi kemungkinan duplicate.

Possible key:

Bank Account

+

Transaction Date

+

Amount

+

Reference.

Jika Reference tersedia:

Gunakan sebagai primary duplicate check.

Jika tidak tersedia:

Gunakan combination matching.

---

## 20. Duplicate Status

NEW

POSSIBLE_DUPLICATE

DUPLICATE

IGNORED

---

# PRD 14H — INTERNAL TRANSACTION

## 21. Purpose

Internal Transaction adalah transaksi yang dapat dicocokkan dengan Bank Transaction.

Sumber dapat berasal dari:

PAYMENT

COLLECTION

DISTRIBUTION

MANUAL

---

## 22. Entity

reconciliation_transactions

Fields:

id

organization_id

source_type

source_id

transaction_reference

transaction_date

amount

currency

direction

status

created_at

updated_at

---

## 23. Source Type

PAYMENT

COLLECTION

DISTRIBUTION

MANUAL

OTHER

---

## 24. Direction

INFLOW

OUTFLOW

---

# PRD 14I — MATCHING

## 25. Purpose

Matching menghubungkan Bank Transaction dengan Internal Transaction.

Entity:

reconciliation_matches

Fields:

id

bank_transaction_id

reconciliation_transaction_id

match_type

matched_amount

confidence_score

matched_by

matched_at

status

created_at

updated_at

---

## 26. Match Type

AUTO

MANUAL

PARTIAL

ADJUSTMENT

---

## 27. Match Status

UNMATCHED

SUGGESTED

MATCHED

PARTIALLY_MATCHED

MISMATCHED

EXCLUDED

---

# PRD 14J — AUTO MATCHING

## 28. Initial Auto Matching

Versi awal menggunakan rule sederhana.

Prioritas:

1. Exact Reference Match.
2. Exact Amount Match.
3. Date Match.
4. Direction Match.

---

## 29. Example

Bank Transaction:

Reference:

PAY2026000001

Amount:

Rp1.000.000

Internal Transaction:

Reference:

PAY2026000001

Amount:

Rp1.000.000

Result:

MATCHED

Confidence:

100.

---

## 30. Amount and Date Matching

Jika Reference tidak tersedia:

Bank:

Rp500.000

Tanggal:

10 Januari 2026.

Internal:

Rp500.000

Tanggal:

10 Januari 2026.

Result:

SUGGESTED.

Tidak langsung otomatis MATCHED kecuali policy mengizinkan.

---

# PRD 14K — MANUAL MATCHING

## 31. Purpose

User dapat memilih transaksi internal yang sesuai dengan transaksi bank.

Flow:

Select Bank Transaction

↓

Search Internal Transaction

↓

Select Transaction

↓

Enter Matched Amount

↓

Confirm

↓

Create Match.

---

## 32. Permission

Manual Matching membutuhkan:

bank_reconciliation.match

---

# PRD 14L — PARTIAL MATCHING

## 33. Purpose

Bank Transaction dapat dicocokkan sebagian.

Contoh:

Bank Transaction:

Rp1.000.000

Internal Transaction:

Rp600.000

Matched:

Rp600.000

Remaining:

Rp400.000

Status:

PARTIALLY_MATCHED.

---

## 34. Rule

Total:

matched_amount

tidak boleh melebihi:

Bank Transaction Amount.

---

# PRD 14M — UNIDENTIFIED TRANSACTION

## 35. Purpose

Transaksi masuk yang belum diketahui sumbernya harus dapat dicatat.

Contoh:

Transfer masuk:

Rp750.000.

Description:

Transfer Online.

Tidak ditemukan:

Payment Reference.

Status:

UNIDENTIFIED.

---

## 36. Resolution

User dapat:

Search Match

Manual Match

Mark as Other

Create Manual Internal Transaction

Exclude

Escalate.

---

# PRD 14N — MISMATCH

## 37. Mismatch

Mismatch terjadi apabila:

Amount berbeda.

Direction berbeda.

Reference tidak sesuai.

Duplicate transaction.

Transaction tidak ditemukan.

---

## 38. Mismatch Resolution

Actions:

Manual Match

Partial Match

Create Adjustment Reference

Exclude

Ignore

Escalate.

---

# PRD 14O — RECONCILIATION SESSION

## 39. Purpose

Reconciliation dilakukan dalam satu Session.

Entity:

reconciliation_sessions

Fields:

id

organization_id

bank_account_id

session_number

period_start

period_end

opening_balance

closing_balance

matched_amount

unmatched_amount

difference_amount

status

started_by

started_at

completed_at

created_at

updated_at

---

## 40. Session Number

Format:

RCS{YEAR}{SEQUENCE}

Contoh:

RCS2026000001

---

## 41. Session Status

DRAFT

OPEN

IN_PROGRESS

COMPLETED

CLOSED

---

# PRD 14P — RECONCILIATION SUMMARY

## 42. Summary

Setiap Session menampilkan:

Opening Balance

Total Credit

Total Debit

Closing Balance

Total Bank Transactions

Matched Transactions

Partially Matched

Unmatched Transactions

Possible Duplicates

Difference Amount

---

## 43. Formula

Expected Closing Balance:

Opening Balance

+

Total Credit

-

Total Debit.

Jika:

Expected Closing Balance

=

Statement Closing Balance

maka:

Balance Valid.

---

# PRD 14Q — RECONCILIATION STATUS

## 44. Transaction Status

UNMATCHED

SUGGESTED

MATCHED

PARTIALLY_MATCHED

MISMATCHED

UNIDENTIFIED

EXCLUDED

---

# PRD 14R — EXCLUSION

## 45. Purpose

Beberapa transaksi tidak perlu direkonsiliasi dengan transaksi internal.

Contoh:

Bank Fee.

Interest.

Administrative Adjustment.

---

## 46. Exclusion

Exclusion wajib memiliki:

Reason

Excluded By

Excluded At.

Contoh reason:

BANK_FEE

ADMINISTRATIVE

INTEREST

OTHER.

---

# PRD 14S — ADJUSTMENT

## 47. Adjustment Principle

Bank Reconciliation tidak membuat perubahan langsung terhadap transaksi bank.

Jika terdapat adjustment:

Buat:

Adjustment Record.

Entity:

reconciliation_adjustments

Fields:

id

reconciliation_session_id

bank_transaction_id

adjustment_type

amount

reason

reference

status

created_by

approved_by

created_at

updated_at

---

## 48. Adjustment Type

BANK_FEE

CORRECTION

ROUNDING

OTHER

Adjustment yang berdampak finansial harus diteruskan ke:

Accounting & Ledger.

---

# PRD 14T — ACCOUNTING INTEGRATION

## 49. Principle

Bank Reconciliation tidak langsung membuat jurnal tanpa proses validasi.

Jika ditemukan:

Bank Fee.

Adjustment.

Correction.

Sistem dapat menghasilkan:

Accounting Event.

Accounting Module bertanggung jawab membuat:

Journal Entry.

---

## 50. Example

Bank Fee:

Bank Transaction

↓

Unmatched

↓

Marked as BANK_FEE

↓

Adjustment Approved

↓

Accounting Event

↓

Journal Entry.

---

# PRD 14U — PAYMENT INTEGRATION

## 51. Payment Matching

Payment dengan status:

PAID

dapat dicocokkan dengan:

Bank Transaction Credit.

Jika:

Reference

dan:

Amount

sesuai.

---

## 52. Mismatch Payment

Jika Payment tercatat PAID tetapi tidak ditemukan Bank Transaction:

Flag:

PAYMENT_NOT_RECONCILED.

Jika Bank Transaction ditemukan tetapi tidak ada Payment:

Flag:

UNIDENTIFIED_INFLOW.

---

# PRD 14V — DISTRIBUTION INTEGRATION

## 53. Distribution Matching

Distribution dengan:

BANK_TRANSFER

dapat dicocokkan dengan:

Bank Transaction Debit.

Reference dapat menggunakan:

Distribution Number

atau:

Provider Transfer Reference.

---

# PRD 14W — API SPECIFICATION

## 54. Bank Accounts

GET

/api/v1/bank-accounts

POST

/api/v1/bank-accounts

GET

/api/v1/bank-accounts/{id}

PATCH

/api/v1/bank-accounts/{id}

POST

/api/v1/bank-accounts/{id}/activate

POST

/api/v1/bank-accounts/{id}/deactivate

---

## 55. Bank Statements

GET

/api/v1/bank-statements

POST

/api/v1/bank-statements/import

GET

/api/v1/bank-statements/{id}

POST

/api/v1/bank-statements/{id}/process

---

## 56. Bank Transactions

GET

/api/v1/bank-transactions

GET

/api/v1/bank-transactions/{id}

POST

/api/v1/bank-transactions/{id}/exclude

POST

/api/v1/bank-transactions/{id}/match

POST

/api/v1/bank-transactions/{id}/unmatch

---

## 57. Reconciliation

GET

/api/v1/reconciliation-sessions

POST

/api/v1/reconciliation-sessions

GET

/api/v1/reconciliation-sessions/{id}

POST

/api/v1/reconciliation-sessions/{id}/start

POST

/api/v1/reconciliation-sessions/{id}/auto-match

POST

/api/v1/reconciliation-sessions/{id}/complete

POST

/api/v1/reconciliation-sessions/{id}/close

---

## 58. Adjustments

POST

/api/v1/reconciliation-adjustments

POST

/api/v1/reconciliation-adjustments/{id}/approve

POST

/api/v1/reconciliation-adjustments/{id}/reject

---

# PRD 14X — PERMISSIONS

## 59. Permission Codes

bank_account.view

bank_account.create

bank_account.update

bank_account.manage

bank_statement.view

bank_statement.import

bank_statement.process

bank_transaction.view

bank_transaction.match

bank_transaction.unmatch

bank_transaction.exclude

bank_reconciliation.view

bank_reconciliation.create

bank_reconciliation.start

bank_reconciliation.auto_match

bank_reconciliation.complete

bank_reconciliation.close

bank_reconciliation.adjustment.create

bank_reconciliation.adjustment.approve

bank_reconciliation.audit.view

---

# PRD 14Y — AUDIT EVENTS

## 60. Audit Events

Minimal:

bank_account_created

bank_account_updated

bank_account_activated

bank_account_deactivated

bank_statement_imported

bank_statement_processed

bank_transaction_imported

bank_transaction_duplicate_detected

bank_transaction_matched

bank_transaction_unmatched

bank_transaction_excluded

bank_transaction_partial_matched

bank_transaction_unidentified

reconciliation_session_created

reconciliation_session_started

reconciliation_auto_match_completed

reconciliation_completed

reconciliation_closed

reconciliation_adjustment_created

reconciliation_adjustment_approved

reconciliation_adjustment_rejected

---

# PRD 14Z — UI REQUIREMENTS

## 61. Bank Account List

ZETRA DataTable.

Columns:

Account Code

Bank

Account Name

Account Number

Currency

Current Balance

Status

Actions

---

## 62. Bank Statement Import

Steps:

Step 1

Select Bank Account

↓

Step 2

Upload CSV atau XLSX

↓

Step 3

Map Columns

↓

Step 4

Preview

↓

Step 5

Validate

↓

Step 6

Import

↓

Step 7

Start Reconciliation

---

## 63. Reconciliation Dashboard

Cards:

Total Bank Transactions

Matched

Partially Matched

Unmatched

Unidentified

Possible Duplicates

Difference Amount

---

## 64. Reconciliation Workspace

Layout:

Left Panel:

Bank Transactions.

Center Panel:

Transaction Detail.

Right Panel:

Suggested Internal Matches.

Actions:

Match

Partial Match

Exclude

Create Adjustment

Unmatch.

---

## 65. Reconciliation Session Detail

Header:

Session Number

Bank Account

Period

Opening Balance

Closing Balance

Difference

Status.

Tabs:

Overview

Matched

Unmatched

Partial

Unidentified

Duplicates

Adjustments

Audit.

---

# PRD 14AA — BUSINESS RULES

## 66. General Rules

1. Bank Account harus berada dalam satu Organization.
2. Account Code harus unik.
3. Nomor rekening harus terenkripsi.
4. Bank Statement tidak boleh mengubah transaksi asli.
5. Duplicate Transaction harus dideteksi.
6. Bank Transaction dapat memiliki satu atau lebih Match.
7. Total Matched Amount tidak boleh melebihi Bank Transaction Amount.
8. Auto Match harus mengikuti configured rules.
9. Suggested Match tidak langsung dianggap MATCHED kecuali policy mengizinkan.
10. Manual Match membutuhkan permission.
11. Unmatched Transaction harus dapat ditelusuri.
12. Unidentified Transaction harus dapat ditangani.
13. Exclusion membutuhkan reason.
14. Adjustment membutuhkan audit trail.
15. Adjustment finansial menghasilkan Accounting Event.
16. Completed Session tidak dapat diubah tanpa reopening.
17. Closed Session bersifat immutable.
18. Payment dapat direkonsiliasi dengan Bank Transaction.
19. Distribution Bank Transfer dapat direkonsiliasi.
20. Organization isolation wajib diterapkan.
21. Permission diperiksa di backend.
22. Semua aktivitas material harus diaudit.

---

# PRD 14AB — TESTING REQUIREMENTS

## 67. Unit Test

Minimal:

- Bank Account Creation
- Account Code Generation
- Account Number Masking
- Statement Creation
- CSV Import
- XLSX Import
- Column Mapping
- Transaction Validation
- Duplicate Detection
- Exact Reference Matching
- Amount Matching
- Date Matching
- Auto Match
- Manual Match
- Partial Match
- Unmatch
- Exclusion
- Adjustment
- Reconciliation Balance Calculation
- Session Completion
- Session Closure

---

## 68. Integration Test

Flow:

Bank Statement Import

↓

Transaction Validation

↓

Duplicate Detection

↓

Internal Transaction Loading

↓

Auto Match

↓

Manual Review

↓

Partial Match

↓

Unidentified Resolution

↓

Adjustment

↓

Accounting Event

↓

Reconciliation Complete

↓

Session Closed.

---

## 69. Security Test

Test:

- Cross organization bank access;
- Unauthorized statement import;
- Unauthorized transaction matching;
- Account number exposure;
- Duplicate import;
- Bank transaction modification;
- Unauthorized adjustment approval;
- Closed session modification;
- Reconciliation manipulation;
- Audit bypass.

---

# PRD 14AC — ACCEPTANCE CRITERIA

- [ ] Bank Account dapat dikelola.
- [ ] Nomor rekening terenkripsi.
- [ ] CSV Import tersedia.
- [ ] XLSX Import tersedia.
- [ ] Column Mapping tersedia.
- [ ] Bank Statement dapat dibuat.
- [ ] Bank Transaction dapat diimpor.
- [ ] Duplicate Detection tersedia.
- [ ] Internal Transaction tersedia.
- [ ] Exact Reference Matching tersedia.
- [ ] Amount Matching tersedia.
- [ ] Date Matching tersedia.
- [ ] Auto Match tersedia.
- [ ] Manual Match tersedia.
- [ ] Partial Match tersedia.
- [ ] Unmatched Transaction tersedia.
- [ ] Unidentified Transaction tersedia.
- [ ] Exclusion tersedia.
- [ ] Adjustment tersedia.
- [ ] Reconciliation Session tersedia.
- [ ] Reconciliation Summary tersedia.
- [ ] Payment Integration tersedia.
- [ ] Distribution Integration tersedia.
- [ ] Accounting Event tersedia.
- [ ] Audit Trail tersedia.
- [ ] Organization isolation diterapkan.
- [ ] Permission diterapkan.
- [ ] Automated Test tersedia.

---

# PRD 14AD — DEFINITION OF DONE

Modul Bank Reconciliation dianggap selesai apabila:

1. Bank Account dapat dibuat dan dikelola.
2. Nomor rekening disimpan dengan aman.
3. Bank Statement dapat diimpor.
4. CSV dan XLSX didukung.
5. Mapping kolom tersedia.
6. Bank Transaction dapat divalidasi.
7. Duplicate Transaction dapat dideteksi.
8. Internal Transaction dapat dimuat.
9. Auto Match sederhana berjalan.
10. Manual Match tersedia.
11. Partial Match tersedia.
12. Unmatched Transaction dapat ditangani.
13. Unidentified Transaction dapat diselesaikan.
14. Adjustment tersedia.
15. Adjustment dapat menghasilkan Accounting Event.
16. Reconciliation Session dapat dibuat.
17. Balance dapat dihitung.
18. Session dapat diselesaikan.
19. Session dapat ditutup.
20. Payment dapat direkonsiliasi.
21. Distribution Bank Transfer dapat direkonsiliasi.
22. Audit Trail tersedia.
23. Organization isolation berjalan.
24. Permission berjalan.
25. Automated Test berhasil.

---

# FUTURE DEVELOPMENT

Fitur berikut dapat dikembangkan pada versi selanjutnya:

- Bank API Integration
- Automatic Bank Synchronization
- Open Banking Integration
- OFX Import
- CAMT Import
- Advanced Matching Rules
- AI Transaction Matching
- Fuzzy Name Matching
- Automatic Reconciliation Schedule
- Real-time Bank Balance
- Multi Currency Reconciliation
- Settlement Management
- Advanced Exception Workflow
- Bank Fee Auto Classification
- Machine Learning Match Confidence

---

# END OF PRD MODULE 14 — BANK RECONCILIATION
### 3.2 Out of Scope

TBD

## 4. Aktor & Role

TBD

## 5. Business Rules

TBD

## 6. Workflow

TBD

## 7. Functional Requirements

TBD

## 8. Non-Functional Requirements

TBD

## 9. Data Model

TBD

## 10. Database Schema

TBD

## 11. API Specification

TBD

## 12. Authorization & Permission

TBD

## 13. Audit Trail

TBD

## 14. UI/UX Requirements

TBD

## 15. Validation & Error Handling

TBD

## 16. Security Requirements

TBD

## 17. Integration

TBD

## 18. Reporting

TBD

## 19. Test Cases

TBD

## 20. Seed Data

TBD

## 21. Acceptance Criteria

TBD

## 22. Open Questions

TBD

## 23. Technical Notes

TBD
